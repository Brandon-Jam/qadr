<?php

namespace App\Controller;

use App\Repository\TournamentParticipantRepository;
use App\Repository\TournamentMatchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
#[Route('/profil', name: 'app_profile')]
public function index(
    TournamentParticipantRepository $participantRepo,
    TournamentMatchRepository $matchRepo
): Response {
    $user = $this->getUser();

    // 🔹 Tous les participants du user
    $participants = $participantRepo->findBy(['user' => $user]);

    if (empty($participants)) {
        // Aucun tournoi => stats vides
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'participations' => [],
            'stats' => [
                'totalMatches' => 0,
                'wins' => 0,
                'losses' => 0,
                'winRate' => 0,
                'tournaments' => 0,
                'avgScore' => 0,
            ],
        ]);
    }

    // 🔹 Tous les matchs liés aux participants
    $matches = $matchRepo->createQueryBuilder('m')
        ->where('m.player1 IN (:participants) OR m.player2 IN (:participants)')
        ->setParameter('participants', $participants)
        ->getQuery()
        ->getResult();

    $totalMatches = count($matches);
    $wins = 0;
    $losses = 0;
    $totalScore = 0;
    $tournaments = [];

    foreach ($matches as $match) {

        // Tournois uniques
        $tournaments[$match->getTournament()->getId()] = true;

        // Récupération du participant courant (celui du user)
        $userParticipant = null;
        foreach ($participants as $p) {
            if ($match->getPlayer1() === $p || $match->getPlayer2() === $p) {
                $userParticipant = $p;
                break;
            }
        }

        if (!$userParticipant) {
            continue;
        }

        // Score selon son camp
        if ($match->getPlayer1() === $userParticipant) {
            $totalScore += $match->getScore1();
        } else {
            $totalScore += $match->getScore2();
        }

        // Victoire / défaite
        if ($match->getWinner() === $userParticipant) {
            $wins++;
        } elseif ($match->getWinner()) {
            $losses++;
        }
    }

    $tournamentCount = count($tournaments);
    $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;
    $avgScore = $totalMatches > 0 ? round($totalScore / $totalMatches, 1) : 0;

    return $this->render('profil/index.html.twig', [
        'user' => $user,
        'participations' => $participants,
        'stats' => [
            'totalMatches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
            'winRate' => $winRate,
            'tournaments' => $tournamentCount,
            'avgScore' => $avgScore,
        ],
    ]);
}}