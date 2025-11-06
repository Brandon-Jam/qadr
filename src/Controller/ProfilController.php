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

        // 🔹 Participations aux tournois
        $participations = $participantRepo->findBy(['user' => $user]);

        // 🔹 Tous les matchs du joueur (player1 ou player2)
        $matches = $matchRepo->createQueryBuilder('m')
            ->where('m.player1 = :user OR m.player2 = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $totalMatches = count($matches);
        $wins = 0;
        $losses = 0;
        $totalScore = 0;
        $tournaments = [];

        foreach ($matches as $match) {
            // Comptabilise les tournois uniques
            $tournaments[$match->getTournament()->getId()] = true;

            // Score du joueur selon son rôle dans le match
            $score = null;
            if ($match->getPlayer1() === $user) {
                $score = $match->getScore1();
            } elseif ($match->getPlayer2() === $user) {
                $score = $match->getScore2();
            }

            if ($score !== null) {
                $totalScore += $score;
            }

            // Victoires / défaites
            if ($match->getWinner() === $user) {
                $wins++;
            } elseif ($match->getWinner() !== null) {
                $losses++;
            }
        }

        $tournamentCount = count($tournaments);
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;
        $avgScore = $totalMatches > 0 ? round($totalScore / $totalMatches, 1) : 0;

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'participations' => $participations,
            'stats' => [
                'totalMatches' => $totalMatches,
                'wins' => $wins,
                'losses' => $losses,
                'winRate' => $winRate,
                'tournaments' => $tournamentCount,
                'avgScore' => $avgScore,
            ],
        ]);
    }
}
