<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\MatchInvite;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentCard;
use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use App\Entity\TournamentParticipantCard;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // 👉 Quand tu vas sur /admin, on redirige automatiquement vers les cartes
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(CardCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Qadr');
    }

   public function configureMenuItems(): iterable
{
    // ----- DASHBOARD -----
    yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

    // ----- CARTES RPG (globales) -----
    yield MenuItem::section('Cartes RPG');
    yield MenuItem::linkToCrud('Cartes', 'fa fa-bolt', Card::class);

    // ----- TOURNOIS -----
    yield MenuItem::section('Tournois');
    yield MenuItem::linkToCrud('Tournois', 'fa fa-trophy', Tournament::class);
    yield MenuItem::linkToCrud('Participants', 'fa fa-users', TournamentParticipant::class);
    yield MenuItem::linkToCrud('Matchs', 'fa fa-gamepad', TournamentMatch::class);
    yield MenuItem::linkToCrud('Cartes jouées', 'fa fa-fire', MatchCardPlay::class);
    yield MenuItem::linkToCrud('Cartes du tournoi', 'fa fa-layer-group', TournamentCard::class);
    yield MenuItem::linkToCrud('Cartes des joueurs', 'fa fa-id-card', TournamentParticipantCard::class);
    yield MenuItem::linkToCrud('Invitations', 'fa fa-envelope', MatchInvite::class);

    

   

    // ----- UTILISATEURS -----
    yield MenuItem::section('Utilisateurs');
    yield MenuItem::linkToCrud('Gestion des utilisateurs', 'fa fa-user', User::class);
}

}
