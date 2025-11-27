<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use App\Entity\TournamentParticipantCard;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CardPlayService
{
    private const MAX_CARDS_PER_MATCH = 3;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * Joue une carte :
     * - possession OK ?
     * - limite 3 OK ?
     * - doublon interdit ?
     * - enregistre MatchCardPlay
     * - décrémente quantité
     * - reset ready
     * - phase = cards
     */
    public function playCard(
        TournamentMatch $match,
        TournamentParticipant $participant,
        Card $card,
        User $user
    ): array {

        $participantCardRepo = $this->em->getRepository(TournamentParticipantCard::class);
        $matchCardRepo       = $this->em->getRepository(MatchCardPlay::class);

        // 1) Possession ?
        $participantCard = $participantCardRepo->findOneBy([
            'participant' => $participant,
            'card'        => $card,
        ]);

        if (!$participantCard || $participantCard->getQuantity() <= 0) {
            return [
                'success' => false,
                'message' => "Vous ne possédez plus cette carte.",
            ];
        }

        // 2) Limite de 3 cartes ?
        $existing = $matchCardRepo->findBy([
            'match'  => $match,
            'usedBy' => $user,
        ]);

        if (count($existing) >= self::MAX_CARDS_PER_MATCH) {
            return [
                'success' => false,
                'message' => "Vous avez déjà utilisé le maximum de 3 cartes.",
            ];
        }

        // 3) Doublon interdit
        foreach ($existing as $play) {
            if ($play->getCard() && $play->getCard()->getId() === $card->getId()) {
                return [
                    'success' => false,
                    'message' => "Vous avez déjà utilisé cette carte dans ce match.",
                ];
            }
        }

        // 4) Enregistrer l'utilisation
        $usage = (new MatchCardPlay())
            ->setCard($card)
            ->setMatch($match)
            ->setUsedBy($user)
            ->setUsedAt(new \DateTimeImmutable());

        // Décrémenter le stock
        $participantCard->setQuantity($participantCard->getQuantity() - 1);

        // 🔥 Dès qu'une carte est jouée : les DEUX joueurs repassent en "not ready"
        if (method_exists($match, 'setPlayer1Ready')) {
            $match->setPlayer1Ready(false);
        }
        if (method_exists($match, 'setPlayer2Ready')) {
            $match->setPlayer2Ready(false);
        }

        // Phase cartes
        $match->setPhase('cards');

        // Persist
        $this->em->persist($usage);
        $this->em->persist($participantCard);
        $this->em->persist($match);
        $this->em->flush();

        return [
            'success' => true,
            'message' => "Carte utilisée ! (la phase cartes doit être revalidée)",
        ];
    }
}
