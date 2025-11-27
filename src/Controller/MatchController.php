<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentMatch;
use App\Service\CardPlayService;
use App\Service\MatchFlowService;
use App\Entity\TournamentParticipant;
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
    // ============================================================
    // 📌 INDEX
    // ============================================================
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        int $tournamentId,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo
    ): Response {
        $tournament = $tournamentRepo->find($tournamentId);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournoi introuvable.');
        }

        $matches = $matchRepo->findBy(['tournament' => $tournament]);

        return $this->render('match/index.html.twig', [
            'tournament' => $tournament,
            'matches'    => $matches,
        ]);
    }

    // ============================================================
    // 📌 CREATE MATCH (ARBITRE)
    // ============================================================
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

        // Vérifie que c'est un arbitre
        if (!$tournament->getReferees()->contains($user)) {
            $this->addFlash('danger', '⛔ Vous n’êtes pas arbitre de ce tournoi.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
        }

        $participants = $participantRepo->findBy(['tournament' => $tournament]);

        if ($request->isMethod('POST')) {

            $player1 = $participantRepo->find($request->request->get('player1'));
            $player2 = $participantRepo->find($request->request->get('player2'));

            if (!$player1 || !$player2 || $player1 === $player2) {
                $this->addFlash('danger', '❌ Sélection invalide.');
                return $this->redirectToRoute('app_tournament_match_new', ['tournamentId' => $tournamentId]);
            }

            $match = (new TournamentMatch())
                ->setTournament($tournament)
                ->setPlayer1($player1)
                ->setPlayer2($player2)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setStartTime(new \DateTimeImmutable())
                ->setPhase('cards')
                ->setIsFinished(false)
                ->setIsValidated(false)
                ->setRound(1);

            $em->persist($match);
            $em->flush();

            $this->addFlash('success', 'Match créé.');
            return $this->redirectToRoute('app_tournament_match_index', ['tournamentId' => $tournamentId]);
        }

        return $this->render('match/new.html.twig', [
            'tournament'   => $tournament,
            'participants' => $participants,
        ]);
    }

    // ============================================================
    // 📌 SHOW MATCH (JOUEURS + ARBITRE)
    // ============================================================
#[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
public function show(
    int $tournamentId,
    int $id,
    TournamentRepository $tournamentRepo,
    TournamentMatchRepository $matchRepo,
    TournamentParticipantRepository $participantRepo,
    EntityManagerInterface $em,
    Security $security,
    MatchFlowService $matchFlowService,
    Request $request
): Response {
    $tournament = $tournamentRepo->find($tournamentId);
    $match      = $matchRepo->find($id);

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournamentId) {
        throw $this->createNotFoundException('Match introuvable.');
    }

    $user        = $security->getUser();
    $participant = $participantRepo->findOneBy([
        'user'       => $user,
        'tournament' => $tournament,
    ]);
    $isReferee = $tournament->getReferees()->contains($user);

    if (!$participant && !$isReferee) {
        $this->addFlash('danger', 'Accès refusé.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // -------------------------------------------------
    // 🔥 FORM POST (score + validate)
    // -------------------------------------------------
    if ($request->isMethod('POST')) {
        $action = $request->request->get('action');

        // 1) Saisie des scores par l’arbitre
        if ($action === 'score' && $isReferee) {
            $score1 = $request->request->getInt('score1');
            $score2 = $request->request->getInt('score2');

            $match->setScore1($score1);
            $match->setScore2($score2);

            if ($score1 > $score2) {
                $match->setWinner($match->getPlayer1());
                $match->setLoser($match->getPlayer2());
            } elseif ($score2 > $score1) {
                $match->setWinner($match->getPlayer2());
                $match->setLoser($match->getPlayer1());
            } else {
                $match->setWinner(null);
                $match->setLoser(null);
            }

            $em->flush();
            $this->addFlash('success', 'Scores enregistrés.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        // 2) Validation finale par arbitre
        if ($action === 'validate' && $isReferee) {
            if ($match->isValidated()) {
                $this->addFlash('danger', 'Match déjà validé.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            if ($match->getScore1() === null || $match->getScore2() === null) {
                $this->addFlash('danger', 'Score manquant.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            if ($match->getScore1() === $match->getScore2()) {
                $this->addFlash('danger', 'Match nul non autorisé.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            // 💥 Résolution complète (HP, crédits, log, etc.)
            $matchFlowService->resolveValidatedMatch($match);

            $this->addFlash('success', 'Match validé ! Effets appliqués.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }
    }

    // -------------------------------------------------
    // 🔥 DONNÉES POUR L’AFFICHAGE
    // -------------------------------------------------
    $matchCardPlayRepo = $em->getRepository(MatchCardPlay::class);

    // Cartes du joueur courant (si joueur)
    $myCards  = [];
    $oppCards = [];

    if ($participant) {
        // 👉 ici on utilise bien le TournamentParticipant, pas le User
        $myCards = $matchCardPlayRepo->findBy(
            [
                'match'  => $match,
                'usedBy' => $participant,
            ],
            ['usedAt' => 'ASC']
        );

        $oppCards = $matchCardPlayRepo->createQueryBuilder('c')
            ->where('c.match = :m')
            ->andWhere('c.usedBy != :p')
            ->setParameter('m', $match)
            ->setParameter('p', $participant)
            ->orderBy('c.usedAt', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        // Arbitre → on ne parle pas de "mes cartes", juste toutes les cartes
        $oppCards = $matchCardPlayRepo->findBy(
            ['match' => $match],
            ['usedAt' => 'ASC']
        );
    }

    // Cartes disponibles pour ce participant
    $availableCards = [];
    if ($participant) {
        $availableCards = $em->getRepository(TournamentParticipantCard::class)
            ->findBy(['participant' => $participant]);
    }

    // Toutes les cartes du match (battle / validated)
    $usedCards = $matchCardPlayRepo->findBy(
        ['match' => $match],
        ['usedAt' => 'ASC']
    );

    return $this->render('match/show.html.twig', [
        'tournament'          => $tournament,
        'match'               => $match,
        'participant'         => $participant,
        'isReferee'           => $isReferee,
        'currentParticipant'  => $participant,
        'myCards'             => $myCards,
        'oppCards'            => $oppCards,
        'availableCards'      => $availableCards,
        'usedCards'           => $usedCards,
        'combatLog'           => $match->getCombatLog() ?? [],
    ]);
}



    // ============================================================
    // 📌 UTILISATION DE CARTE
    // ============================================================
    #[Route('/{id}/use-card/{cardId}', name: 'use_card', methods: ['POST'])]
    public function useCard(
        int $tournamentId,
        int $id,
        int $cardId,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo,
        TournamentParticipantRepository $participantRepo,
        EntityManagerInterface $em,
        CardPlayService $cardPlayService
    ): Response {

        $user = $this->getUser();

        $tournament = $tournamentRepo->find($tournamentId);
        $match      = $matchRepo->find($id);
        $card       = $em->getRepository(Card::class)->find($cardId);

        if (!$tournament || !$match || !$card) {
            throw $this->createNotFoundException('Données invalides.');
        }

        if ($match->isFinished() || $match->isValidated()) {
            $this->addFlash('danger', 'Match terminé.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        if ($match->getPhase() !== 'cards') {
            $this->addFlash('danger', 'Phase cartes terminée.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        $participant = $participantRepo->findOneBy([
            'user'       => $user,
            'tournament' => $tournament,
        ]);

        if (!$participant || !in_array($participant->getId(), [
            $match->getPlayer1()->getId(),
            $match->getPlayer2()->getId(),
        ])) {
            $this->addFlash('danger', 'Vous ne participez pas à ce match.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        // 💥 Service clean
        $result = $cardPlayService->playCard($match, $participant, $card, $user);

        $this->addFlash($result['success'] ? 'success' : 'danger', $result['message']);

        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id'           => $id,
        ]);
    }

    // ============================================================
    // 📌 PLAYER READY
    // ============================================================
    #[Route('/{id}/ready', name: 'set_ready', methods: ['POST'])]
    public function setReady(
        int $tournamentId,
        int $id,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo,
        TournamentParticipantRepository $participantRepo,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();
        $tournament = $tournamentRepo->find($tournamentId);
        $match = $matchRepo->find($id);

        if (!$tournament || !$match) {
            throw $this->createNotFoundException('Match invalide.');
        }

        if ($match->isFinished() || $match->isValidated()) {
            $this->addFlash('danger', 'Match terminé.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        if ($match->getPhase() !== 'cards') {
            $this->addFlash('danger', 'Phase cartes terminée.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        $participant = $participantRepo->findOneBy([
            'user'       => $user,
            'tournament' => $tournament,
        ]);

        if (!$participant) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
        }

        $isP1 = $match->getPlayer1()->getId() === $participant->getId();
        $isP2 = $match->getPlayer2()->getId() === $participant->getId();

        if (!$isP1 && !$isP2) {
            $this->addFlash('danger', 'Vous ne jouez pas ce match.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        if ($isP1) $match->setPlayer1Ready(true);
        if ($isP2) $match->setPlayer2Ready(true);

        if ($match->isPlayer1Ready() && $match->isPlayer2Ready()) {
            $match->setPhase('battle');
            $this->addFlash('success', 'Phase cartes terminée.');
        } else {
            $this->addFlash('info', 'En attente de l’adversaire.');
        }

        $em->persist($match);
        $em->flush();

        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id'           => $id,
        ]);
    }

    // ============================================================
    // 📌 CANCEL READY
    // ============================================================
    #[Route('/{id}/cancel-ready', name: 'cancel_ready', methods: ['POST'])]
    public function cancelReady(
        int $tournamentId,
        int $id,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo,
        TournamentParticipantRepository $participantRepo,
        EntityManagerInterface $em,
        Security $security
    ): Response {

        $user       = $security->getUser();
        $tournament = $tournamentRepo->find($tournamentId);
        $match      = $matchRepo->find($id);

        if (!$tournament || !$match) {
            throw $this->createNotFoundException('Match invalide.');
        }

        $participant = $participantRepo->findOneBy([
            'user'       => $user,
            'tournament' => $tournament,
        ]);

        if (!$participant) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_tournament_show', [
                'id' => $tournamentId
            ]);
        }

        if ($match->getPlayer1()->getId() === $participant->getId()) {
            $match->setPlayer1Ready(false);
        }

        if ($match->getPlayer2()->getId() === $participant->getId()) {
            $match->setPlayer2Ready(false);
        }

        $match->setPhase('cards');

        $em->persist($match);
        $em->flush();

        $this->addFlash('info', 'Statut prêt annulé.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id'           => $id,
        ]);
    }
}
