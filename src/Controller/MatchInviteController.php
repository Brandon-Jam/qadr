<?php

namespace App\Controller;

use App\Entity\MatchInvite;
use App\Entity\Tournament;
use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MatchInviteController extends AbstractController
{
    #[Route('/tournament/{id}/challenge/{opponentId}', name: 'match_invite_send')]
    public function send(
        Tournament $tournament,
        int $opponentId,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        $challenger = $em->getRepository(TournamentParticipant::class)->findOneBy([
            'user' => $user,
            'tournament' => $tournament
        ]);

        $opponent = $em->getRepository(TournamentParticipant::class)->find($opponentId);

        // Prevent self-challenge
        if ($challenger->getId() === $opponent->getId()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas vous défier vous-même.");
        }

        $matchRepo = $em->getRepository(TournamentMatch::class);
        $participantRepo = $em->getRepository(TournamentParticipant::class);

        // Count alive players (HP > 0)
        $alive = $participantRepo->countAlivePlayers($tournament);

        // Restriction active seulement si plus de 8 joueurs vivants
        if ($alive > 8) {
            $wins = $matchRepo->countWinsBetween($challenger, $opponent);
            if ($wins >= 2) {
                $this->addFlash(
                    'danger',
                    "Limite de 2 victoires déjà atteinte contre ce joueur (active car plus de 8 joueurs vivants)."
                );
                return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
            }
        }

        // Existing pending invite
        $existing = $em->getRepository(MatchInvite::class)->findOneBy([
            'challenger' => $challenger,
            'opponent' => $opponent,
            'status' => 'pending'
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Défi déjà envoyé à ce joueur.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Create invite
        $invite = new MatchInvite();
        $invite->setChallenger($challenger);
        $invite->setOpponent($opponent);
        $invite->setTournament($tournament);

        $em->persist($invite);
        $em->flush();

        $this->addFlash('success', 'Défi envoyé !');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }


    #[Route('/tournament/invite/{inviteId}/accept', name: 'match_invite_accept')]
    public function accept(int $inviteId, EntityManagerInterface $em): Response
    {
        $invite = $em->getRepository(MatchInvite::class)->find($inviteId);
        $matchRepo = $em->getRepository(TournamentMatch::class);
        $participantRepo = $em->getRepository(TournamentParticipant::class);

        $challenger = $invite->getChallenger();
        $opponent = $invite->getOpponent();
        $tournament = $invite->getTournament();

        // Count alive players
        $alive = $participantRepo->countAlivePlayers($tournament);

        // Restriction active seulement si plus de 8 joueurs vivants
        if ($alive > 8) {
            $wins = $matchRepo->countWinsBetween($challenger, $opponent);
            if ($wins >= 2) {
                $this->addFlash(
                    'danger',
                    "Vous ne pouvez pas accepter : limite de 2 victoires déjà atteinte (active car plus de 8 joueurs vivants)."
                );
                return $this->redirectToRoute('app_tournament_show', [
                    'id' => $tournament->getId()
                ]);
            }
        }

        // Accept invite
        $invite->setStatus('accepted');

        // Create match automatically
        $match = new TournamentMatch();
        $match->setTournament($tournament);
        $match->setPlayer1($challenger);
        $match->setPlayer2($opponent);
        $match->setScore1(3);
        $match->setScore2(3);
        $match->setStartTime(new \DateTimeImmutable());
        $match->setCreatedAt(new \DateTimeImmutable());
        $match->setStatus('ongoing');
        $match->setIsValidated(false);

        $em->persist($match);
        $em->flush();

        return $this->redirectToRoute('app_tournament_match_index', [
            'tournamentId' => $tournament->getId()
        ]);
    }


    #[Route('/tournament/invite/{inviteId}/refuse', name: 'match_invite_refuse')]
    public function refuse(int $inviteId, EntityManagerInterface $em): Response
    {
        $invite = $em->getRepository(MatchInvite::class)->find($inviteId);
        $invite->setStatus('refused');
        $em->flush();

        return $this->redirectToRoute('app_tournament_show', [
            'id' => $invite->getTournament()->getId()
        ]);
    }
}
