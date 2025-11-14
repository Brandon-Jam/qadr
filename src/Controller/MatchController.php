<?php

namespace App\Controller;

use App\Entity\Card;
use App\Service\CardEngine;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentMatch;
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

    // Vérifie que c’est un arbitre
    if (!$tournament->getReferees()->contains($user)) {
        $this->addFlash('danger', '⛔ Vous n’êtes pas arbitre de ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // Récupère les participants
    $participants = $participantRepo->findBy(['tournament' => $tournament]);

    if ($request->isMethod('POST')) {

        $player1Id = $request->request->get('player1');
        $player2Id = $request->request->get('player2');

        if (!$player1Id || !$player2Id || $player1Id === $player2Id) {
            $this->addFlash('danger', '❌ Sélection invalide. Choisissez deux joueurs différents.');
            return $this->redirectToRoute('app_tournament_match_new', ['tournamentId' => $tournamentId]);
        }

        $player1 = $participantRepo->find($player1Id);
        $player2 = $participantRepo->find($player2Id);

        if (!$player1 || !$player2) {
            $this->addFlash('danger', 'Erreur : joueurs introuvables.');
            return $this->redirectToRoute('app_tournament_match_new', ['tournamentId' => $tournamentId]);
        }

        // CREATION DU MATCH
        $match = new TournamentMatch();
        $match->setTournament($tournament);
        $match->setPlayer1($player1);
        $match->setPlayer2($player2);
        $match->setCreatedAt(new \DateTimeImmutable());
        $match->setStartTime(new \DateTimeImmutable());
        $match->setIsValidated(false);
        $match->setIsFinished(false);
        $match->setRound(1); // round numérique

        $em->persist($match);
        $em->flush();

        $this->addFlash('success', '✅ Match créé avec succès !');
        return $this->redirectToRoute('app_tournament_match_index', ['tournamentId' => $tournamentId]);
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
    CardEngine $cardEngine,
    Request $request
): Response {
    $tournament = $tournamentRepo->find($tournamentId);
    $match      = $matchRepo->find($id);

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournamentId) {
        throw $this->createNotFoundException('Match ou tournoi invalide.');
    }

    $user = $security->getUser();

    // Participant ou arbitre ?
    $participant = $participantRepo->findOneBy([
        'user'       => $user,
        'tournament' => $tournament,
    ]);

    $isReferee = $tournament->getReferees()->contains($user);

    if (!$participant && !$isReferee) {
        $this->addFlash('danger', 'Accès refusé.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // ------------------------------------------------------------------
    // 🔥 GESTION SCORE / VALIDATION ARBITRE
    // ------------------------------------------------------------------
    if ($request->isMethod('POST')) {

        $action = $request->request->get('action');

        // SCORE (referee)
        if ($action === 'score' && $isReferee) {

            $score1 = $request->request->get('score1');
            $score2 = $request->request->get('score2');

            $score1 = ($score1 !== '' && $score1 !== null) ? (int)$score1 : null;
            $score2 = ($score2 !== '' && $score2 !== null) ? (int)$score2 : null;

            $match->setScore1($score1);
            $match->setScore2($score2);

            if ($score1 !== null && $score2 !== null) {
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
            }

            $em->flush();
            $this->addFlash('success', 'Scores enregistrés.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }

        // VALIDATION PAR ARBITRE
        if ($action === 'validate' && $isReferee) {

            if ($match->isValidated()) {
                $this->addFlash('warning', 'Match déjà validé.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            if ($match->getScore1() === null || $match->getScore2() === null) {
                $this->addFlash('danger', 'Scores manquants.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            if ($match->getScore1() === $match->getScore2()) {
                $this->addFlash('danger', 'Match nul interdit.');
                return $this->redirectToRoute('app_tournament_match_show', [
                    'tournamentId' => $tournamentId,
                    'id'           => $id,
                ]);
            }

            // ------------------------------------------------------
            // 🃏 1) Récupérer les cartes jouées (entités MatchCardPlay)
            // ------------------------------------------------------
            $p1Plays = $em->getRepository(MatchCardPlay::class)->findBy([
                'match'  => $match,
                'usedBy' => $match->getPlayer1()->getUser(),
            ]);

            $p2Plays = $em->getRepository(MatchCardPlay::class)->findBy([
                'match'  => $match,
                'usedBy' => $match->getPlayer2()->getUser(),
            ]);

            // 🔹 Séparer par trigger : on_use, on_win, after_match
            $p1OnUse      = [];
            $p1OnWin      = [];
            $p1AfterMatch = [];

            foreach ($p1Plays as $play) {
                $card = $play->getCard();
                if (!$card) {
                    continue;
                }

                switch ($card->getTrigger()) {
                    case 'on_use':
                        $p1OnUse[] = $play;
                        break;
                    case 'on_win':
                        $p1OnWin[] = $play;
                        break;
                    case 'after_match':
                        $p1AfterMatch[] = $play;
                        break;
                }
            }

            $p2OnUse      = [];
            $p2OnWin      = [];
            $p2AfterMatch = [];

            foreach ($p2Plays as $play) {
                $card = $play->getCard();
                if (!$card) {
                    continue;
                }

                switch ($card->getTrigger()) {
                    case 'on_use':
                        $p2OnUse[] = $play;
                        break;
                    case 'on_win':
                        $p2OnWin[] = $play;
                        break;
                    case 'after_match':
                        $p2AfterMatch[] = $play;
                        break;
                }
            }

            // ------------------------------------------------------
            // 🧠 2) Résolution via CardEngine (HP du tournoi) - on_use
            // ------------------------------------------------------
            $result = $cardEngine->resolveCards($p1OnUse, $p2OnUse);
            // $result = ['p1_damage','p2_damage','p1_heal','p2_heal','log'=>[...]]

            $p1 = $match->getPlayer1(); // TournamentParticipant
            $p2 = $match->getPlayer2();

            $hp1 = $p1->getHp();
            $hp2 = $p2->getHp();

            // Dégâts des cartes on_use
            $hp1 = $hp1 - $result['p1_damage'];
            $hp2 = $hp2 - $result['p2_damage'];

            // Soins on_use
            $hp1 = $hp1 + $result['p1_heal'];
            $hp2 = $hp2 + $result['p2_heal'];

            // Clamp [0,10] (tu peux changer max plus tard)
            $hp1 = max(0, min(10, $hp1));
            $hp2 = max(0, min(10, $hp2));

            $p1->setHp($hp1);
            $p2->setHp($hp2);

            // ------------------------------------------------------
            // 🏆 3) Déterminer winner/loser sur base des SCORES SMASH
            // ------------------------------------------------------
            if ($match->getScore1() > $match->getScore2()) {
                $winner = $p1;
                $loser  = $p2;

                $winnerOnWin      = $p1OnWin;
                $winnerAfterMatch = $p1AfterMatch;
                $loserAfterMatch  = $p2AfterMatch;
            } else {
                $winner = $p2;
                $loser  = $p1;

                $winnerOnWin      = $p2OnWin;
                $winnerAfterMatch = $p2AfterMatch;
                $loserAfterMatch  = $p1AfterMatch;
            }

            $match->setWinner($winner);
            $match->setLoser($loser);

            // ------------------------------------------------------
            // 🔥 4) Appliquer les cartes on_win (seulement si victoire)
            // ------------------------------------------------------
            foreach ($winnerOnWin as $play) {
                $card = $play->getCard();
                if (!$card) {
                    continue;
                }

                $stat     = $card->getStat();     // hp ou damage
                $operator = $card->getOperator(); // + ou -
                $value    = $card->getValue();

                if ($value === 0) {
                    continue;
                }

                if ($stat === 'hp') {
                    // Bouclier / soin sur le gagnant
                    $hp = $winner->getHp();
                    if ($operator === '+') {
                        $hp += $value;
                    } elseif ($operator === '-') {
                        $hp -= $value;
                    }
                    $hp = max(0, min(10, $hp));
                    $winner->setHp($hp);
                }

                if ($stat === 'damage') {
                    // Exemple : Boule de feu = dégâts en plus sur le perdant
                    $hp = $loser->getHp();
                    if ($operator === '+') {
                        $hp -= $value; // +damage = -HP pour l’adversaire
                    } elseif ($operator === '-') {
                        $hp += $value; // cas exotique
                    }
                    $hp = max(0, min(10, $hp));
                    $loser->setHp($hp);
                }
            }

            // ------------------------------------------------------
            // 💊 5) Appliquer les cartes after_match (toujours)
            // ------------------------------------------------------
            foreach ($winnerAfterMatch as $play) {
                $card = $play->getCard();
                if (!$card) {
                    continue;
                }

                $stat     = $card->getStat();
                $operator = $card->getOperator();
                $value    = $card->getValue();

                if ($value === 0) {
                    continue;
                }

                if ($stat === 'hp') {
                    $hp = $winner->getHp();
                    if ($operator === '+') {
                        $hp += $value;
                    } elseif ($operator === '-') {
                        $hp -= $value;
                    }
                    $hp = max(0, min(10, $hp));
                    $winner->setHp($hp);
                }

                if ($stat === 'damage') {
                    $hp = $loser->getHp();
                    if ($operator === '+') {
                        $hp -= $value;
                    } elseif ($operator === '-') {
                        $hp += $value;
                    }
                    $hp = max(0, min(10, $hp));
                    $loser->setHp($hp);
                }
            }

            foreach ($loserAfterMatch as $play) {
                $card = $play->getCard();
                if (!$card) {
                    continue;
                }

                $stat     = $card->getStat();
                $operator = $card->getOperator();
                $value    = $card->getValue();

                if ($value === 0) {
                    continue;
                }

                if ($stat === 'hp') {
                    $hp = $loser->getHp();
                    if ($operator === '+') {
                        $hp += $value;
                    } elseif ($operator === '-') {
                        $hp -= $value;
                    }
                    $hp = max(0, min(10, $hp));
                    $loser->setHp($hp);
                }

                if ($stat === 'damage') {
                    $hp = $winner->getHp();
                    if ($operator === '+') {
                        $hp -= $value;
                    } elseif ($operator === '-') {
                        $hp += $value;
                    }
                    $hp = max(0, min(10, $hp));
                    $winner->setHp($hp);
                }
            }

            // ------------------------------------------------------
            // 💰 6) Crédits + HP de base pour le perdant (-1)
            // ------------------------------------------------------
            $winner->setCredits($winner->getCredits() + 10);
            $winner->setCreditsEarned($winner->getCreditsEarned() + 10);
            $loser->setCredits($loser->getCredits() + 5);
            $loser->setCreditsEarned($loser->getCreditsEarned() + 5);

            // Perdant perd 1 HP en plus des cartes
            $loser->setHp(max(0, $loser->getHp() - 1));

            // Vérifier élimination
            $p1->checkElimination();
            $p2->checkElimination();

            // ------------------------------------------------------
            // ✅ 7) Statut du match
            // ------------------------------------------------------
            $match->setIsValidated(true);
            $match->setIsFinished(true);
            $match->setPhase('validated');

            $em->persist($match);
            $em->persist($winner);
            $em->persist($loser);
            $em->flush();

            $this->addFlash('success', 'Match validé ! Cartes appliquées et HP du tournoi mis à jour.');
            return $this->redirectToRoute('app_tournament_match_show', [
                'tournamentId' => $tournamentId,
                'id'           => $id,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // 🔥 DONNÉES POUR TWIG
    // ------------------------------------------------------------------

    // Cartes du joueur courant
    $myCards = $em->getRepository(MatchCardPlay::class)->findBy([
        'match'  => $match,
        'usedBy' => $user,
    ]);

    // Nombre de cartes adverses
    $oppCardsCount = $em->getRepository(MatchCardPlay::class)
        ->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->where('c.match = :m')
        ->andWhere('c.usedBy != :me')
        ->setParameter('m', $match)
        ->setParameter('me', $user)
        ->getQuery()
        ->getSingleScalarResult();

    // Cartes disponibles
    $availableCards = [];
    if ($participant) {
        $availableCards = $em->getRepository(TournamentParticipantCard::class)
            ->findBy(['participant' => $participant]);
    }

    // Toutes les cartes jouées (pour battle / validated)
    $usedCards = $em->getRepository(MatchCardPlay::class)
        ->findBy(['match' => $match], ['usedAt' => 'ASC']);

    return $this->render('match/show.html.twig', [
        'tournament'     => $tournament,
        'match'          => $match,
        'participant'    => $participant,
        'myCards'        => $myCards,
        'oppCardsCount'  => $oppCardsCount,
        'availableCards' => $availableCards,
        'usedCards'      => $usedCards,
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

    // 🔹 Récupération des entités de base
    $tournament = $tournamentRepo->find($tournamentId);
    $match      = $matchRepo->find($id);
    $card       = $em->getRepository(Card::class)->find($cardId);

    if (!$tournament || !$match || !$card || $match->getTournament()->getId() !== $tournamentId) {
        throw $this->createNotFoundException('Données invalides.');
    }

    // 🔹 Sécurité : match déjà terminé / validé / plus en phase "cartes"
    if (method_exists($match, 'isFinished') && $match->isFinished()) {
        $this->addFlash('danger', 'Le match est terminé — impossible d’utiliser une carte.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }

    if (method_exists($match, 'isValidated') && $match->isValidated()) {
        $this->addFlash('danger', 'Le match est déjà validé — impossible d’utiliser une carte.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }

    if (method_exists($match, 'getPhase') && $match->getPhase() !== 'cards') {
        $this->addFlash('danger', 'La phase des cartes est terminée — vous ne pouvez plus utiliser de cartes.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }

    // 🔹 Récupération du participant lié à l'utilisateur
    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant) {
        $this->addFlash('danger', 'Accès refusé. Vous n’êtes pas joueur de ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', [
            'id' => $tournamentId,
        ]);
    }

    // 🔹 Vérifier que ce participant est bien dans CE match (player1 ou player2)
    if ($match->getPlayer1()?->getId() !== $participant->getId()
        && $match->getPlayer2()?->getId() !== $participant->getId()) {
        $this->addFlash('danger', 'Vous ne participez pas à ce match.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $match->getId(),
        ]);
    }

    // 🔹 Vérifie que le joueur possède la carte
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

    // 🔹 Enregistrer l’utilisation de la carte
    $usage = (new MatchCardPlay())
        ->setCard($card)
        ->setMatch($match)
        ->setUsedBy($user)   // ⚠️ si plus tard tu passes à TournamentParticipant, on adaptera ici
        ->setUsedAt(new \DateTimeImmutable());

    // Décrémenter la quantité
    $participantCard->setQuantity($participantCard->getQuantity() - 1);

    $em->persist($usage);
    $em->persist($participantCard);

    // Dès qu'une carte est utilisée, les deux joueurs repassent en "non prêts"
if (method_exists($match, 'setPlayer1Ready')) {
    $match->setPlayer1Ready(false);
}
if (method_exists($match, 'setPlayer2Ready')) {
    $match->setPlayer2Ready(false);
}

// 🔹 On repasse EN FORCÉ dans la phase "cards"
if (method_exists($match, 'setPhase')) {
    $match->setPhase('cards');
}

    $em->persist($match);
    $em->flush();

    $this->addFlash('success', 'Carte utilisée ! (La phase des cartes doit être revalidée par les deux joueurs)');

    return $this->redirectToRoute('app_tournament_match_show', [
        'tournamentId' => $tournamentId,
        'id' => $match->getId(),
    ]);
}

#[Route('/{id}/ready', name: 'set_ready', methods: ['POST'])]
public function setReady(
    int $tournamentId,
    int $id,
    CardEngine $cardEngine,
    TournamentRepository $tournamentRepo,
    TournamentMatchRepository $matchRepo,
    TournamentParticipantRepository $participantRepo,
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();

    $tournament = $tournamentRepo->find($tournamentId);
    $match      = $matchRepo->find($id);

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournamentId) {
        throw $this->createNotFoundException('Match ou tournoi invalide.');
    }

    // Match déjà fini ou validé → on bloque
    if ($match->isFinished() || $match->isValidated()) {
        $this->addFlash('danger', 'Ce match est terminé — vous ne pouvez plus modifier la phase des cartes.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $id,
        ]);
    }

    // Phase cartes obligatoire
    if ($match->getPhase() !== 'cards') {
        $this->addFlash('danger', 'La phase des cartes est déjà terminée.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $id,
        ]);
    }

    // Participant courant
    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant) {
        $this->addFlash('danger', 'Vous n’êtes pas joueur de ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournamentId]);
    }

    // Vérifie que ce participant est bien dans ce match
    $isPlayer1 = $match->getPlayer1() && $match->getPlayer1()->getId() === $participant->getId();
    $isPlayer2 = $match->getPlayer2() && $match->getPlayer2()->getId() === $participant->getId();

    if (!$isPlayer1 && !$isPlayer2) {
        $this->addFlash('danger', 'Vous ne participez pas à ce match.');
        return $this->redirectToRoute('app_tournament_match_show', [
            'tournamentId' => $tournamentId,
            'id' => $id,
        ]);
    }

    // Marquer ce joueur comme "prêt"
    if ($isPlayer1) {
        $match->setPlayer1Ready(true);
    } elseif ($isPlayer2) {
        $match->setPlayer2Ready(true);
    }

    // Si les deux sont prêts → passer en phase "battle"
    if ($match->isPlayer1Ready() && $match->isPlayer2Ready()) {
        // Récupérer cartes des deux joueurs
    $p1Cards = $em->getRepository(MatchCardPlay::class)
        ->findBy(['match' => $match, 'usedBy' => $match->getPlayer1()->getUser()]);

    $p2Cards = $em->getRepository(MatchCardPlay::class)
        ->findBy(['match' => $match, 'usedBy' => $match->getPlayer2()->getUser()]);

    

        $match->setPhase('battle');
        // Optionnel : flash “la phase de cartes est terminée”
        $this->addFlash('success', 'La phase des cartes est terminée. Le match peut maintenant être joué.');
    } else {
        $this->addFlash('info', 'En attente de validation de la phase cartes par l’adversaire.');
    }

    $em->persist($match);
    $em->flush();

    return $this->redirectToRoute('app_tournament_match_show', [
        'tournamentId' => $tournamentId,
        'id' => $id,
    ]);
}
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
    $tournament = $tournamentRepo->find($tournamentId);
    $match = $matchRepo->find($id);
    $user = $security->getUser();

    if (!$tournament || !$match || $match->getTournament()->getId() !== $tournamentId) {
        throw $this->createNotFoundException('Match invalide.');
    }

    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant) {
        $this->addFlash('danger', 'Accès refusé.');
        return $this->redirectToRoute('app_tournament_show', [
            'id' => $tournamentId
        ]);
    }

    // Le joueur annule son statut prêt
    if ($match->getPlayer1()->getId() === $participant->getId()) {
        $match->setPlayer1Ready(false);
    }

    if ($match->getPlayer2()->getId() === $participant->getId()) {
        $match->setPlayer2Ready(false);
    }

    // Si un annule → la phase reste en mode "cards"
    $match->setPhase('cards');

    $em->persist($match);
    $em->flush();

    $this->addFlash('info', '⛔ Statut prêt annulé.');
    return $this->redirectToRoute('app_tournament_match_show', [
        'tournamentId' => $tournamentId,
        'id' => $id
    ]);
}
/**
 * Sépare les MatchCardPlay selon le trigger
 */
private function splitPlaysByTrigger(array $plays): array
{
    $byTrigger = [
        'on_use'      => [],
        'on_win'      => [],
        'after_match' => [],
    ];

    foreach ($plays as $play) {
        $card = $play->getCard();
        if (!$card) continue;

        $trigger = $card->getTrigger(); // on_use, on_win, on_lose, after_match
        if (isset($byTrigger[$trigger])) {
            $byTrigger[$trigger][] = $play;
        }
    }

    return $byTrigger;
}

/**
 * Applique l’effet d’une Card sur owner/opponent
 */
private function applyCardEffect(Card $card, TournamentParticipant $owner, TournamentParticipant $opponent): void
{
    $stat  = $card->getStat();     // hp, damage, shield
    $op    = $card->getOperator(); // + / -
    $value = $card->getValue();

    if ($value === 0) return;

    // hp : modifie les HP du propriétaire
    if ($stat === 'hp') {
        $hp = $owner->getHp();
        $hp = ($op === '+') ? $hp + $value : $hp - $value;
        $owner->setHp($this->clampHp($hp));
    }

    // damage : dégâts sur l’adversaire
    if ($stat === 'damage') {
        $hp = $opponent->getHp();
        $hp = ($op === '+') ? $hp - $value : $hp + $value;
        $opponent->setHp($this->clampHp($hp));
    }

    // shield : à gérer plus tard
}

/**
 * Clamp HP entre 0 et 10
 */
private function clampHp(int $hp, int $min = 0, int $max = 10): int
{
    return max($min, min($max, $hp));
}


}

