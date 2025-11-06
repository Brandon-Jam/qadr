<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentMatch;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TournamentParticipantCard;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\TournamentMatchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TournamentParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
public function new(
    int $tournamentId,
    Request $request,
    TournamentRepository $tournamentRepo,
    TournamentParticipantRepository $participantRepo,
    EntityManagerInterface $em,
    Security $security
): Response {
    $tournament = $tournamentRepo->find($tournamentId);
    $user = $security->getUser();

    if (!$tournament) {
        throw $this->createNotFoundException('Tournoi introuvable.');
    }

    // ✅ Vérifie que l'utilisateur est arbitre de ce tournoi
    if (!$tournament->getReferees()->contains($user)) {
        $this->addFlash('danger', '⛔ Vous n’êtes pas arbitre de ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // ✅ Récupère les participants pour peupler le formulaire
    $participants = $participantRepo->findBy(['tournament' => $tournament]);

    if ($request->isMethod('POST')) {
        $player1Id = $request->request->get('player1');
        $player2Id = $request->request->get('player2');

        if ($player1Id && $player2Id && $player1Id !== $player2Id) {
            $player1 = $participants[array_search($player1Id, array_column($participants, 'id'))] ?? null;
            $player2 = $participants[array_search($player2Id, array_column($participants, 'id'))] ?? null;

            // ⚠️ Sécurité : on récupère les bons User
            $player1User = $participantRepo->find($player1Id)->getUser();
            $player2User = $participantRepo->find($player2Id)->getUser();

            $match = new \App\Entity\TournamentMatch();
            $match->setTournament($tournament);
            $match->setPlayer1($player1User);
            $match->setPlayer2($player2User);
            $match->setCreatedAt(new \DateTimeImmutable());
            $match->setStartTime(new \DateTimeImmutable());
            $match->setRound('Phase 1');
            $match->setStatus('pending');

            $em->persist($match);
            $em->flush();

            $this->addFlash('success', '✅ Match créé avec succès !');
            return $this->redirectToRoute('app_tournament_match_index', ['tournamentId' => $tournamentId]);
        }

        $this->addFlash('danger', '❌ Sélection invalide : choisissez deux joueurs différents.');
    }

    return $this->render('match/new.html.twig', [
        'tournament' => $tournament,
        'participants' => $participants,
    ]);
}

#[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
public function show(
    int $tournamentId,
    int $id,
    TournamentRepository $tournamentRepo,
    TournamentMatchRepository $matchRepo,
    TournamentParticipantRepository $participantRepo,
    EntityManagerInterface $em,
    Security $security,
    Request $request
): Response {
    $tournament = $tournamentRepo->find($tournamentId);
    $match = $matchRepo->find($id);

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournament->getId()) {
        throw $this->createNotFoundException('Match ou tournoi invalide.');
    }

    $user = $security->getUser();

    // ✅ Vérifier que l'utilisateur participe bien au tournoi
    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    // ✅ Vérifier si l'utilisateur est arbitre du tournoi (en tenant compte du ManyToMany)
    $isReferee = $tournament->getReferees()->contains($user);

    if (!$participant && !$isReferee) {
        $this->addFlash('danger', 'Accès refusé : vous n’êtes ni joueur ni arbitre de ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // ✅ Gestion des POST (score + validation)
    if ($request->isMethod('POST')) {
        $action = $request->request->get('action');

        // --- Saisie des scores ---
        if ($action === 'score') {
            if ($isReferee) {
                $score1Raw = $request->request->get('score1');
$score2Raw = $request->request->get('score2');

$score1 = ($score1Raw !== '' && $score1Raw !== null) ? (int) $score1Raw : null;
$score2 = ($score2Raw !== '' && $score2Raw !== null) ? (int) $score2Raw : null;

                $match->setScore1($score1 ?: null);
                $match->setScore2($score2 ?: null);

                if ($score1 !== null && $score2 !== null) {
                    if ($score1 > $score2) {
                        $match->setWinner($match->getPlayer1());
                    } elseif ($score2 > $score1) {
                        $match->setWinner($match->getPlayer2());
                    } else {
                        $match->setWinner(null); // égalité
                    }
                }

                $em->flush();
                $this->addFlash('success', '🏆 Scores enregistrés avec succès !');
            } else {
                $this->addFlash('danger', 'Accès refusé pour modifier les scores.');
            }

            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id' => $id,
            ]);
        }

        // --- Validation du match ---
        if ($action === 'validate') {
            if ($isReferee) {
                $match->setIsValidated(true);

                $winner = null;
                $loser = null;

                // Détermination du gagnant selon les scores
                if ($match->getScore1() !== null && $match->getScore2() !== null) {
                    if ($match->getScore1() > $match->getScore2()) {
                        $winner = $match->getPlayer1();
                        $loser = $match->getPlayer2();
                    } elseif ($match->getScore2() > $match->getScore1()) {
                        $winner = $match->getPlayer2();
                        $loser = $match->getPlayer1();
                    }
                }

                // Attribution des crédits
                if ($winner && $loser) {
                    $winnerParticipant = $participantRepo->findOneBy([
                        'user' => $winner,
                        'tournament' => $match->getTournament(),
                    ]);
                    $loserParticipant = $participantRepo->findOneBy([
                        'user' => $loser,
                        'tournament' => $match->getTournament(),
                    ]);

                    if ($winnerParticipant && $loserParticipant) {
                        $winnerParticipant->setCredits($winnerParticipant->getCredits() + 10);
                        $loserParticipant->setCredits($loserParticipant->getCredits() + 5);
                        $em->persist($winnerParticipant);
                        $em->persist($loserParticipant);
                    }
                }

                $em->flush();
                $this->addFlash('success', '✅ Match validé et crédits attribués !');
            }

            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id' => $id,
            ]);
        }
    }

    // --- Cartes disponibles et cartes utilisées ---
    $availableCards = [];
    if ($participant) {
        $availableCards = $em->getRepository(TournamentParticipantCard::class)
            ->createQueryBuilder('c')
            ->where('c.participant = :participant')
            ->andWhere('c.quantity > 0')
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getResult();
    }

    $usedCards = $em->getRepository(\App\Entity\MatchCardPlay::class)
        ->findBy(['match' => $match], ['usedAt' => 'DESC']);

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

    // ✅ Match déjà fini ?
    if ($match->getScore1() <= 0 || $match->getScore2() <= 0) {
        $this->addFlash('danger', 'Le match est terminé — impossible d’utiliser une carte.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }

    // ✅ Participant
    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant && $tournament->getReferee() !== $user) {
    $this->addFlash('danger', 'Accès refusé. Vous n’êtes ni joueur ni arbitre de ce tournoi.');
    return $this->redirectToRoute('app_tournament_show', [
        'id' => $tournamentId,
    ]);
}

    // ✅ Vérifie que le joueur possède la carte
    $participantCard = $em->getRepository(TournamentParticipantCard::class)->findOneBy([
        'participant' => $participant,
        'card' => $card,
    ]);

    if (!$participantCard || $participantCard->getQuantity() <= 0) {
        $this->addFlash('danger', 'Vous ne possédez plus cette carte.');
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

    // Décrémente la quantité
    $participantCard->setQuantity($participantCard->getQuantity() - 1);

    $em->persist($usage);
    $em->persist($participantCard);

    // ✅ Vérifie si le match est terminé après cette action
    if ($match->getScore1() <= 0 || $match->getScore2() <= 0) {
        // Déterminer le gagnant / perdant
        $winner = $match->getScore1() > $match->getScore2() ? $match->getPlayer1() : $match->getPlayer2();
        $loser  = $match->getScore1() > $match->getScore2() ? $match->getPlayer2() : $match->getPlayer1();

        // Récupérer leurs participations dans ce tournoi
        $winnerParticipant = $participantRepo->findOneBy([
            'user' => $winner,
            'tournament' => $tournament,
        ]);

        $loserParticipant = $participantRepo->findOneBy([
            'user' => $loser,
            'tournament' => $tournament,
        ]);

        // ✅ Récompenser
        if ($winnerParticipant && $loserParticipant) {
            $winnerParticipant->setCredits($winnerParticipant->getCredits() + 10);
            $loserParticipant->setCredits($loserParticipant->getCredits() + 5);
             // 🧠 Vérification ici
        dump([
            'winnerUser' => $winner->getUserIdentifier(),
            'loserUser' => $loser->getUserIdentifier(),
            'winnerParticipantId' => $winnerParticipant->getId(),
            'loserParticipantId' => $loserParticipant->getId(),
            'winnerCreditsAfter' => $winnerParticipant->getCredits(),
            'loserCreditsAfter' => $loserParticipant->getCredits(),
        ]);
            $em->persist($winnerParticipant);
            $em->persist($loserParticipant);
        }

        // ✅ Mettre le statut du match à "finished"
        $match->setStatus('finished');
        $em->persist($match);

        $this->addFlash('success', 'Le match est terminé ! Le gagnant reçoit +10 crédits, le perdant +5.');
    }

    $em->flush();

    return $this->redirectToRoute('app_tournament_match_show', [
        'tournamentId' => $tournamentId,
        'id' => $match->getId(),
    ]);
}



}

