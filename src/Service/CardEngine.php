<?php
namespace App\Service;

use App\Entity\MatchCardPlay;

class CardEngine
{
    public function resolveCards(array $p1Cards, array $p2Cards): array
    {
        $result = [
            'p1_damage' => 0,
            'p2_damage' => 0,
            'p1_heal'   => 0,
            'p2_heal'   => 0,
            'log'       => [],
        ];

        // 1️⃣ Contresort
        $this->handleNegate($p1Cards, $p2Cards, $result);

        // 2️⃣ Attaque
        $this->handleAttack($p1Cards, $p2Cards, $result);

        // 3️⃣ Défense
        $this->handleDefense($p1Cards, $p2Cards, $result);

        // 4️⃣ Heal
        $this->handleHeal($p1Cards, $p2Cards, $result);

        return $result;
    }


    // ============================================================
    // 🔥 CONTRESORT – annule aléatoirement une carte adverse
    // ============================================================
    private function handleNegate(array &$p1, array &$p2, array &$result)
    {
        // P1 annule carte P2
        $neg1 = array_filter($p1, fn(MatchCardPlay $play) =>
            $play->getCard()->getStat() === 'negate'
        );

        if (count($neg1) > 0) {

            $p2Usable = array_filter($p2, fn(MatchCardPlay $play) =>
                $play->getCard()->getStat() !== 'negate'
            );

            if (count($p2Usable) > 0) {
                $keys = array_keys($p2Usable);
                $randomKey = $keys[array_rand($keys)];
                $removedCard = $p2[$randomKey]->getCard()->getName();

                unset($p2[$randomKey]);
                $p2 = array_values($p2);

                $result['log'][] = "🛑 Contresort (P1) annule : $removedCard";
            }
        }

        // P2 annule carte P1
        $neg2 = array_filter($p2, fn(MatchCardPlay $play) =>
            $play->getCard()->getStat() === 'negate'
        );

        if (count($neg2) > 0) {

            $p1Usable = array_filter($p1, fn(MatchCardPlay $play) =>
                $play->getCard()->getStat() !== 'negate'
            );

            if (count($p1Usable) > 0) {
                $keys = array_keys($p1Usable);
                $randomKey = $keys[array_rand($keys)];
                $removedCard = $p1[$randomKey]->getCard()->getName();

                unset($p1[$randomKey]);
                $p1 = array_values($p1);

                $result['log'][] = "🛑 Contresort (P2) annule : $removedCard";
            }
        }
    }


    // ============================================================
    // 🔥 ATTACK
    // ============================================================
    private function handleAttack(array $p1, array $p2, array &$result)
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'attack') {
                $result['p2_damage'] += $card->getValue();
                $result['log'][] = "🔥 P1 utilise {$card->getName()} → {$card->getValue()} dmg";
            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'attack') {
                $result['p1_damage'] += $card->getValue();
                $result['log'][] = "🔥 P2 utilise {$card->getName()} → {$card->getValue()} dmg";
            }
        }
    }


    // ============================================================
    // 🔥 DEFENSE
    // ============================================================
    private function handleDefense(array $p1, array $p2, array &$result)
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'defense') {
                $result['p1_damage'] = max(0, $result['p1_damage'] - $card->getValue());
                $result['log'][] = "🛡️ P1 réduit les dégâts de {$card->getValue()}";
            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'defense') {
                $result['p2_damage'] = max(0, $result['p2_damage'] - $card->getValue());
                $result['log'][] = "🛡️ P2 réduit les dégâts de {$card->getValue()}";
            }
        }
    }


    // ============================================================
    // 🔥 HEAL
    // ============================================================
    private function handleHeal(array $p1, array $p2, array &$result)
    {
        foreach ($p1 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'heal') {
                $result['p1_heal'] += $card->getValue();
                $result['log'][] = "💚 P1 se soigne de {$card->getValue()}";
            }
        }

        foreach ($p2 as $play) {
            $card = $play->getCard();
            if ($card->getType() === 'heal') {
                $result['p2_heal'] += $card->getValue();
                $result['log'][] = "💚 P2 se soigne de {$card->getValue()}";
            }
        }
    }
}
