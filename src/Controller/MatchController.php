<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentParticipantCard;
use App\Repository\TournamentRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tournament/{tournamentId}/match', name: 'app_tournament_match_')]
class MatchController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        int $tournamentId,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo
    ): Response {
        $tournament = $tournamentRepo->find($tournamentId);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournoi non trouvé.');
        }

        $matches = $matchRepo->findBy(['tournament' => $tournament]);

        return $this->render('match/index.html.twig', [
            'tournament' => $tournament,
            'matches' => $matches,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
public function show(
    int $tournamentId,
    int $id,
    TournamentRepository $tournamentRepo,
    TournamentMatchRepository $matchRepo,
    TournamentParticipantRepository $participantRepo,
    EntityManagerInterface $em
): Response {
    $tournament = $tournamentRepo->find($tournamentId);
    $match = $matchRepo->find($id);

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournament->getId()) {
        throw $this->createNotFoundException('Match ou tournoi invalide.');
    }

    $user = $this->getUser();

    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant) {
        $this->addFlash('danger', 'Vous ne participez pas à ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', [
            'id' => $tournamentId,
        ]);
    }

    // ✅ Cartes disponibles pour ce joueur
    $availableCards = $em->getRepository(TournamentParticipantCard::class)
    ->createQueryBuilder('c')
    ->where('c.participant = :participant')
    ->andWhere('c.quantity > 0')
    ->setParameter('participant', $participant)
    ->getQuery()
    ->getResult();

    // ✅ Cartes déjà utilisées dans ce match
    $usedCards = $em->getRepository(\App\Entity\MatchCardPlay::class)->findBy(
        ['match' => $match],
        ['usedAt' => 'DESC'] // tri du plus récent au plus ancien
    );

    return $this->render('match/show.html.twig', [
        'tournament' => $tournament,
        'match' => $match,
        'availableCards' => $availableCards,
        'usedCards' => $usedCards,
    ]);
}


    #[Route('/{id}/use-card/{cardId}', name: 'use_card', methods: ['POST'])]
    public function useCard(
        int $tournamentId,
        int $id,
        int $cardId,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo,
        TournamentParticipantRepository $participantRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();

        $tournament = $tournamentRepo->find($tournamentId);
        $match = $matchRepo->find($id);
        $card = $em->getRepository(Card::class)->find($cardId);

        if (!$tournament || !$match || !$card || $match->getTournament()->getId() !== $tournamentId) {
            throw $this->createNotFoundException('Données invalides.');
        }

        $participant = $participantRepo->findOneBy([
            'user' => $user,
            'tournament' => $tournament,
        ]);

        if (!$participant) {
            $this->addFlash('danger', 'Vous ne participez pas à ce tournoi.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id' => $match->getId(),
            ]);
        }

        // ✅ Vérifie que le joueur possède cette carte
        $participantCard = $em->getRepository(TournamentParticipantCard::class)->findOneBy([
            'participant' => $participant,
            'card' => $card,
        ]);

        if (!$participantCard || $participantCard->getQuantity() <= 0) {
            $this->addFlash('danger', 'Vous ne possédez pas cette carte ou vous l’avez déjà utilisée.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id' => $match->getId(),
            ]);
        }

        // ✅ Enregistre l’utilisation
        $usage = (new MatchCardPlay())
            ->setCard($card)
            ->setMatch($match)
            ->setUsedBy($user)
            ->setUsedAt(new \DateTime());

        // ✅ Décrémente la quantité
        $participantCard->setQuantity($participantCard->getQuantity() - 1);

        $em->persist($usage);
        $em->persist($participantCard);
        $em->flush();

        $this->addFlash('success', 'Carte activée : ' . $card->getEffect());

        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }
}
