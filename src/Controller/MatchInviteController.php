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

        // prevent self-challenge
        if ($challenger->getId() === $opponent->getId()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas vous défier vous-même.");
        }

        // check if already pending invite
        $existing = $em->getRepository(MatchInvite::class)->findOneBy([
            'challenger' => $challenger,
            'opponent' => $opponent,
            'status' => 'pending'
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Défi déjà envoyé à ce joueur.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

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

        $invite->setStatus('accepted');

        // Auto-create the match
        $match = new TournamentMatch();
        $match->setTournament($invite->getTournament());
        $match->setPlayer1($invite->getChallenger());
        $match->setPlayer2($invite->getOpponent());
        $match->setScore1(3);
        $match->setScore2(3);
        $match->setStartTime(new \DateTimeImmutable());
        $match->setCreatedAt(new \DateTimeImmutable());
        
        $em->persist($match);
        $em->flush();

        return $this->redirectToRoute('app_tournament_match_index', [
            'tournamentId' => $invite->getTournament()->getId()
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
