<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\MatchInvite;
use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\RequireActiveTournament;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MatchInviteController extends AbstractController
{
    #[Route('/tournament/{id}/challenge/{opponentId}', name: 'match_invite_send')]
    #[RequireActiveTournament]
    public function send(
        Tournament $tournament,
        int $opponentId,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour envoyer un défi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        $participantRepo = $em->getRepository(TournamentParticipant::class);
        $matchRepo       = $em->getRepository(TournamentMatch::class);
        $inviteRepo      = $em->getRepository(MatchInvite::class);

        // Joueur qui envoie le défi
        $challenger = $participantRepo->findOneBy([
            'user' => $user,
            'tournament' => $tournament,
        ]);

        if (!$challenger) {
            $this->addFlash('danger', "Vous n'êtes pas inscrit à ce tournoi.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Joueur défié
        $opponent = $participantRepo->find($opponentId);

        if (!$opponent || $opponent->getTournament()->getId() !== $tournament->getId()) {
            $this->addFlash('danger', "Joueur introuvable dans ce tournoi.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 🔴 Pas d’auto-défi
        if ($challenger->getId() === $opponent->getId()) {
            $this->addFlash('danger', "Vous ne pouvez pas vous défier vous-même.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 🔴 Pas de défi si l’un est éliminé
        if ($opponent->isEliminated() || $challenger->isEliminated()) {
            $this->addFlash('danger', 'Un joueur éliminé ne peut pas participer à un match.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // 🔴 Pas de défi si l'un des deux a déjà un match en cours
        $activeChallengerMatch = $matchRepo->findActiveMatchForParticipant($challenger);
        $activeOpponentMatch   = $matchRepo->findActiveMatchForParticipant($opponent);

        if ($activeChallengerMatch) {
            $this->addFlash('danger', "Vous avez déjà un match en cours. Terminez-le avant d'envoyer un défi.");
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournament->getId(),
                'id' => $activeChallengerMatch->getId(),
            ]);
        }

        if ($activeOpponentMatch) {
            $this->addFlash('danger', "Ce joueur a déjà un match en cours. Impossible de le défier pour le moment.");
            return $this->redirectToRoute('app_tournament_show', [
                'id' => $tournament->getId(),
            ]);
        }

        // 🚫 Pas de défi si un match NON TERMINÉ existe déjà entre eux
        $ongoingMatch = $matchRepo->getOngoingMatchBetween($challenger, $opponent);

        if ($ongoingMatch) {
            $this->addFlash('danger', "Vous êtes déjà dans un match en cours contre ce joueur.");
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Nombre de joueurs encore en vie (HP > 0)
        $alive = $participantRepo->countAlivePlayers($tournament);

        // Limite de 2 victoires si plus de 8 joueurs vivants
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

        // Vérifier qu'une invitation n'existe pas déjà
        $existing = $inviteRepo->findOneBy([
            'challenger' => $challenger,
            'opponent'   => $opponent,
            'tournament' => $tournament,
            'status'     => 'pending',
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Défi déjà envoyé à ce joueur.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
        }

        // Création de l'invitation
        $invite = new MatchInvite();
        $invite->setChallenger($challenger);
        $invite->setOpponent($opponent);
        $invite->setTournament($tournament);
        $invite->setStatus('pending');

        $em->persist($invite);
        $em->flush();

        $this->addFlash('success', 'Défi envoyé !');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }

    #[Route('/tournament/invite/{inviteId}/accept', name: 'match_invite_accept')]
    #[RequireActiveTournament]
    public function accept(int $inviteId, EntityManagerInterface $em): Response
    {
        $inviteRepo      = $em->getRepository(MatchInvite::class);
        $matchRepo       = $em->getRepository(TournamentMatch::class);
        $participantRepo = $em->getRepository(TournamentParticipant::class);

        $invite = $inviteRepo->find($inviteId);

        if (!$invite) {
            throw $this->createNotFoundException('Invitation introuvable.');
        }

        $challenger = $invite->getChallenger();
        $opponent   = $invite->getOpponent();
        $tournament = $invite->getTournament();

        // 🔒 Empêcher un joueur déjà engagé d’en accepter un autre
        $activeChallengerMatch = $matchRepo->findActiveMatchForParticipant($challenger);
        $activeOpponentMatch   = $matchRepo->findActiveMatchForParticipant($opponent);

        if ($activeChallengerMatch) {
            $this->addFlash('danger', "❌ Vous avez déjà un match en cours. Terminez-le avant d'en accepter un autre.");
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournament->getId(),
                'id' => $activeChallengerMatch->getId(),
            ]);
        }

        if ($activeOpponentMatch) {
            $this->addFlash('danger', "❌ L'adversaire a déjà un match en cours. Impossible de lancer un second match.");
            return $this->redirectToRoute('app_tournament_show', [
                'id' => $tournament->getId(),
            ]);
        }

        // 🚨 Empêcher un match si un joueur est éliminé
        if ($challenger->isEliminated() || $opponent->isEliminated()) {
            $this->addFlash('danger', 'Un joueur éliminé ne peut plus participer à un match.');
            return $this->redirectToRoute('app_tournament_show', [
                'id' => $tournament->getId()
            ]);
        }

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

        // Accepter l'invitation
        $invite->setStatus('accepted');

        // Création du match
        $match = new TournamentMatch();
        $match->setTournament($tournament);
        $match->setPlayer1($challenger);
        $match->setPlayer2($opponent);
        $match->setScore1(3);
        $match->setScore2(3);
        $match->setStartTime(new \DateTimeImmutable());
        $match->setCreatedAt(new \DateTimeImmutable());
        $match->setIsValidated(false);
        $match->setIsFinished(false);

        $em->persist($match);
        $em->flush();

        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournament->getId(),
            'id' => $match->getId()
        ]);
    }

    #[Route('/tournament/invite/{inviteId}/refuse', name: 'match_invite_refuse')]
    #[RequireActiveTournament]
    public function refuse(int $inviteId, EntityManagerInterface $em): Response
    {
        $invite = $em->getRepository(MatchInvite::class)->find($inviteId);

        if (!$invite) {
            throw $this->createNotFoundException('Invitation introuvable.');
        }

        $invite->setStatus('refused');
        $em->flush();

        return $this->redirectToRoute('app_tournament_show', [
            'id' => $invite->getTournament()->getId()
        ]);
    }
}
