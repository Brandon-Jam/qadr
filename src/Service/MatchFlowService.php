<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use Doctrine\ORM\EntityManagerInterface;

class MatchFlowService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CardEngine $cardEngine
    ) {
    }

    /**
     * Résout entièrement un match déjà scoré.
     * Retourne un LOG complet des actions.
     */
    public function resolveValidatedMatch(TournamentMatch $match): array
    {
       $log = [];

$p1 = $match->getPlayer1();
$p2 = $match->getPlayer2();

$p1User = $p1->getUser();
$p2User = $p2->getUser();

// 1) Cartes jouées
$p1Plays = $this->em->getRepository(MatchCardPlay::class)->findBy([
    'match'  => $match,
    'usedBy' => $p1User,
]);

$p2Plays = $this->em->getRepository(MatchCardPlay::class)->findBy([
    'match'  => $match,
    'usedBy' => $p2User,
]);

// 2) NEGATE global (retire une carte adverse au hasard)
$this->applyNegateGlobal($p1Plays, $p2Plays, $log);

// 3) Split par triggers
$p1Trig = $this->splitTriggers($p1Plays);
$p2Trig = $this->splitTriggers($p2Plays);

// 4) ON_USE → via CardEngine (attaque / défense / heal)
$result = $this->cardEngine->resolveUsePhase(
    $p1Trig['on_use'] ?? [],
    $p2Trig['on_use'] ?? [],
    $p1,
    $p2
);

// 🔥 Fusion des logs CardEngine → dans le log général
if (isset($result['log']) && is_array($result['log'])) {
    foreach ($result['log'] as $entry) {
        $log[] = $entry;
    }
}

// Application HP on_use
$p1->setHp($this->clamp($p1->getHp() - $result['p1_damage'] + $result['p1_heal']));
$p2->setHp($this->clamp($p2->getHp() - $result['p2_damage'] + $result['p2_heal']));

        // 5) Winner / loser
        if ($match->getScore1() > $match->getScore2()) {
            $winner = $p1;
            $loser  = $p2;

            $winnerOnWin  = $p1Trig['on_win'];
            $winnerAfter  = $p1Trig['after_match'];
            $loserAfter   = $p2Trig['after_match'];

        } else {
            $winner = $p2;
            $loser  = $p1;

            $winnerOnWin  = $p2Trig['on_win'];
            $winnerAfter  = $p2Trig['after_match'];
            $loserAfter   = $p1Trig['after_match'];
        }

        $match->setWinner($winner);
        $match->setLoser($loser);

        // 6) Cartes ON_WIN
        foreach ($winnerOnWin as $play) {
            $card = $play->getCard();
            $log[] = "🏆 {$winner->getUser()->getPseudo()} utilise {$card->getName()} sur {$loser->getUser()->getPseudo()} (effet : {$card->getOperator()}{$card->getValue()} {$card->getStat()})";
            $this->applyCardEffect($card, $winner, $loser);
        }

        // 7) After_match winner
        foreach ($winnerAfter as $play) {
            $card = $play->getCard();
            $log[] = "✨ After_match (winner) : {$card->getName()}";
            $this->applyCardEffect($card, $winner, $loser);
        }

        // 8) After_match loser
        foreach ($loserAfter as $play) {
            $card = $play->getCard();
            $log[] = "✨ After_match (loser) : {$card->getName()}";
            $this->applyCardEffect($card, $loser, $winner);
        }

        // 9) Crédit + malus
        $winner->setCredits($winner->getCredits() + 10);
        $loser->setCredits($loser->getCredits() + 5);

        $winnerName = $winner->getUser()->getPseudo();
        $loserName  = $loser->getUser()->getPseudo();
        
        $log[] = "💰 10 crédits pour le gagnant : {$winnerName}";
        $log[] = "💰 3 crédits pour le perdant : {$loserName}";

        $loser->setHp($this->clamp($loser->getHp() - 1));
        $log[] = "💔 $loserName perd 1 HP";

        // 10) Élimination
$p1->checkElimination();
$p2->checkElimination();

if ($p1->isEliminated()) $log[] = "☠️ {$p1->getUser()->getPseudo()} est éliminé !";
if ($p2->isEliminated()) $log[] = "☠️ {$p2->getUser()->getPseudo()} est éliminé !";

// 11) Fin du match
$match->setPhase('validated');
$match->setIsFinished(true);
$match->setIsValidated(true);

// 12) HP finaux
$log[] = "❤️ HP finaux : {$p1->getUser()->getPseudo()} = {$p1->getHp()}, {$p2->getUser()->getPseudo()} = {$p2->getHp()}";

// 13) 🔥 SAUVEGARDE DU LOG DANS LA BDD
$match->setCombatLog($log);

// 14) Persist / flush
$this->em->persist($match);
$this->em->persist($p1);
$this->em->persist($p2);
$this->em->flush();

return $log;
    }

    private function splitTriggers(array $plays): array
    {
        $res = [
            'before_match' => [],
            'on_use'       => [],
            'on_win'       => [],
            'on_lose'      => [],
            'after_match'  => [],
            'during_match' => [],
        ];

        foreach ($plays as $play) {
            $card = $play->getCard();
            if (!$card) continue;

            $trigger = $card->getTrigger();
            if (isset($res[$trigger])) {
                $res[$trigger][] = $play;
            }
        }

        return $res;
    }

    private function applyCardEffect(Card $card, TournamentParticipant $owner, TournamentParticipant $opponent): void
    {
        $stat = $card->getStat();
        $op   = $card->getOperator();
        $val  = $card->getValue();

        if ($stat === 'hp') {
            $hp = $owner->getHp();
            $owner->setHp($this->clamp($op === '+' ? $hp + $val : $hp - $val));
        }

        if ($stat === 'damage') {
            $hp = $opponent->getHp();
            $opponent->setHp($this->clamp($op === '+' ? $hp - $val : $hp + $val));
        }
    }

    private function clamp(int $value, int $min = 0, int $max = 10): int
    {
        return max($min, min($max, $value));
    }

    private function applyNegateGlobal(array &$p1Plays, array &$p2Plays, array &$log): void
    {
        // NEGATE de P1 → annule une carte de P2
        $p1Negates = array_filter($p1Plays, fn($p) =>
            $p->getCard()->getStat() === 'negate' || $p->getCard()->getType() === 'negate'
        );

        if (count($p1Negates) > 0) {
            $usable = array_filter($p2Plays, fn($p) =>
                $p->getCard()->getStat() !== 'negate' && $p->getCard()->getType() !== 'negate'
            );

            if (count($usable) > 0) {
                $keys = array_keys($usable);
                $randomKey = $keys[array_rand($keys)];
                $removed = $p2Plays[$randomKey];

                $log[] = "🛑 P1 annule : " . $removed->getCard()->getName();

                unset($p2Plays[$randomKey]);
                $p2Plays = array_values($p2Plays);
            }
        }

        // NEGATE de P2 → annule une carte de P1
        $p2Negates = array_filter($p2Plays, fn($p) =>
            $p->getCard()->getStat() === 'negate' || $p->getCard()->getType() === 'negate'
        );

        if (count($p2Negates) > 0) {
            $usable = array_filter($p1Plays, fn($p) =>
                $p->getCard()->getStat() !== 'negate' && $p->getCard()->getType() !== 'negate'
            );

            if (count($usable) > 0) {
                $keys = array_keys($usable);
                $randomKey = $keys[array_rand($keys)];
                $removed = $p1Plays[$randomKey];

                $log[] = "🛑 P2 annule : " . $removed->getCard()->getName();

                unset($p1Plays[$randomKey]);
                $p1Plays = array_values($p1Plays);
            }
        }
    }
}
