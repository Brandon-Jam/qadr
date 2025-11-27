<?php
namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\TournamentRepository;
use App\Repository\MatchCardPlayRepository;
use App\Repository\TournamentMatchRepository;
use App\Repository\TournamentParticipantCardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/stats', name: 'admin_stats')]
class AdminStatsController extends AbstractController
{
    #[Route('', name: '')]
    public function index(
        UserRepository $userRepo,
        TournamentRepository $tournamentRepo,
        TournamentMatchRepository $matchRepo,
        TournamentParticipantCardRepository $tournamentParticipantCardRepository,
        MatchCardPlayRepository $cardPlayRepo
    ): Response {
        return $this->render('admin/stats.html.twig', [
            'totalUsers' => $userRepo->count([]),
            'totalTournaments' => $tournamentRepo->count([]),
            'totalMatches' => $matchRepo->count([]),
            'totalCardsPlayed' => $cardPlayRepo->count([]),
            'topCards' => $cardPlayRepo->getTopCardsGlobal(),
            'topCardsByTournament' => $cardPlayRepo->getTopCardsByTournament(),
        ]);
    }
}