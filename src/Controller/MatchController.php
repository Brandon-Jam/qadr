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

    $user = $this->getUser();

    // Vérifier que l'utilisateur participe bien au tournoi
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

    $isReferee = $this->isGranted('ROLE_REFEREE') || $match->getTournament()->getReferee() === $user;

    // Gestion des POST (score + validate)
    if ($request->isMethod('POST')) {
        $action = $request->request->get('action');

        // ✅ Saisie des scores par le referee
        if ($action === 'score') {
            if ($isReferee) {
                $score1Raw = $request->request->get('score1');
                $score2Raw = $request->request->get('score2');

                $score1 = ($score1Raw !== '' && $score1Raw !== null) ? (int) $score1Raw : null;
                $score2 = ($score2Raw !== '' && $score2Raw !== null) ? (int) $score2Raw : null;

                $match->setScore1($score1);
                $match->setScore2($score2);

                // ✅ Détermine le vainqueur uniquement si les deux scores sont renseignés
                if ($score1 !== null && $score2 !== null) {
                    if ($score1 > $score2) {
                        $match->setWinner($match->getPlayer1());
                    } elseif ($score2 > $score1) {
                        $match->setWinner($match->getPlayer2());
                    } else {
                        $match->setWinner(null); // égalité
                    }
                } else {
                    $match->setWinner(null);
                }

                $em->flush();
                $this->addFlash('success', '🏆 Scores enregistrés avec succès !');

                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id' => $id,
                ]);
            } else {
                $this->addFlash('danger', 'Accès refusé pour modifier les scores.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id' => $id,
                ]);
            }
        }

        // ✅ Validation du match
if ($request->isMethod('POST') && $request->request->get('action') === 'validate') {
    if ($this->isGranted('ROLE_REFEREE') || $match->getTournament()->getReferee() === $user) {
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

        // 💰 Attribution des crédits via TournamentParticipant
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

        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $id,
        ]);
    }
}
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
        ['usedAt' => 'DESC']
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

