<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use App\Entity\Tournament;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
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
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Cartes RPG');
        yield MenuItem::linkToCrud('Cartes', 'fa fa-bolt', Card::class);

        yield MenuItem::section('Tournois');
        yield MenuItem::linkToCrud('Tournois', 'fa fa-trophy', Tournament::class);
    }
}
