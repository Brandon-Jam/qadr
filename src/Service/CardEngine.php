<?php

namespace App\Service;

use App\Entity\MatchCardPlay;

class CardEngine
{
    /**
     * Résout les cartes "on_use" selon l’ordre d’utilisation.
     *
     * On reçoit aussi $p1 et $p2 pour avoir les pseudos !
     */
    public function resolveUsePhase(
        array $p1Cards,
        array $p2Cards,
        $p1,
        $p2
    ): array {

        // Ordre = usedAt
        usort($p1Cards, fn(MatchCardPlay $a, MatchCardPlay $b) => $a->getUsedAt() <=> $b->getUsedAt());
        usort($p2Cards, fn(MatchCardPlay $a, MatchCardPlay $b) => $a->getUsedAt() <=> $b->getUsedAt());

        $result = [
            'p1_damage' => 0,
            'p2_damage' => 0,
            'p1_heal'   => 0,
            'p2_heal'   => 0,
            'log'       => [],
        ];

        // 🔥 1) Contresort
        $this->handleNegate($p1Cards, $p2Cards, $result, $p1, $p2, false);
        $this->handleNegate($p2Cards, $p1Cards, $result, $p2, $p1, true);

        // 🔥 2) Attaque
        $this->handleAttack($p1Cards, $p2Cards, $result, $p1, $p2);

        // 🔥 3) Défense
        $this->handleDefense($p1Cards, $p2Cards, $result, $p1, $p2);

        // 🔥 4) Heal
        $this->handleHeal($p1Cards, $p2Cards, $result, $p1, $p2);

        return $result;
    }

    // ============================================================
    // 🔥 CONTRESORT RANDOM
    // ============================================================
    private function handleNegate(
        array &$attacker,
        array &$defender,
        array &$result,
        $userA,
        $userB,
        bool $isP2
    ): void {

        $negates = array_filter($attacker, fn(MatchCardPlay $p) =>
            $p->getCard() && $p->getCard()->getStat() === 'negate'
        );

        if (count($negates) === 0) {
            return;
        }

        // On ne peut pas annuler un negate
        $usable = array_filter($defender, fn(MatchCardPlay $p) =>
            $p->getCard() && $p->getCard()->getStat() !== 'negate'
        );

        if (count($usable) === 0) {
            return;
        }

        $keys       = array_keys($usable);
        $randomKey  = $keys[array_rand($keys)];
        $removed    = $defender[$randomKey];
        $removedName = $removed->getCard()->getName();

        unset($defender[$randomKey]);
        $defender = array_values($defender);

        $result['log'][] =
            "🛑 {$userA->getUser()->getPseudo()} utilise Contresort et annule la carte « {$removedName} » de {$userB->getUser()->getPseudo()}";
    }

    // ============================================================
    // 🔥 ATTACK
    // ============================================================
    private function handleAttack(array $p1, array $p2, array &$result, $player1, $player2): void
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'attack') {
                $v = $card->getValue();
                $result['p2_damage'] += $v;

                $result['log'][] =
                    "🔥 {$player1->getUser()->getPseudo()} utilise {$card->getName()} → {$v} dégâts sur {$player2->getUser()->getPseudo()}";
            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'attack') {
                $v = $card->getValue();
                $result['p1_damage'] += $v;

                $result['log'][] =
                    "🔥 {$player2->getUser()->getPseudo()} utilise {$card->getName()} → {$v} dégâts sur {$player1->getUser()->getPseudo()}";
            }
        }
    }

    // ============================================================
    // 🔥 DEFENSE
    // ============================================================
    private function handleDefense(array $p1, array $p2, array &$result, $player1, $player2): void
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'defense') {
                $v = $card->getValue();
                $result['p1_damage'] = max(0, $result['p1_damage'] - $v);

                $result['log'][] =
                    "🛡️ {$player1->getUser()->getPseudo()} réduit les dégâts reçus de {$v}";
            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'defense') {
                $v = $card->getValue();
                $result['p2_damage'] = max(0, $result['p2_damage'] - $v);

                $result['log'][] =
                    "🛡️ {$player2->getUser()->getPseudo()} réduit les dégâts reçus de {$v}";
            }
        }
    }

    // ============================================================
    // 🔥 HEAL
    // ============================================================
    private function handleHeal(array $p1, array $p2, array &$result, $player1, $player2): void
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'heal') {
                $v = $card->getValue();
                $result['p1_heal'] += $v;
                
                $result['log'][] = "💚 {$player1->getUser()->getPseudo()} utilise {$card->getName()} et se soigne de {$v}";

            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card && $card->getType() === 'heal') {
                $v = $card->getValue();
                $result['p2_heal'] += $v;

                $result['log'][] = "💚 {$player2->getUser()->getPseudo()} utilise {$card->getName()} et se soigne de {$v}";
            }
        }
    }
}
