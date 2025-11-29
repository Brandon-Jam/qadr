<?php

namespace App\EventListener;

use App\Security\RequireActiveTournament;
use App\Repository\TournamentRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Tournament;

class RequireActiveTournamentListener
{
    public function __construct(
        private TournamentRepository $tournamentRepo,
        private Security $security
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Not a controller method → ignore
        if (!is_array($controller)) {
            return;
        }

         $method = new \ReflectionMethod($controller[0], $controller[1]);
    $class  = new \ReflectionClass($controller[0]);

    // 🔥 Vérifie si l'attribut est sur la MÉTHODE
    $methodAttr = $method->getAttributes(RequireActiveTournament::class);

    // 🔥 Vérifie si l'attribut est sur la CLASSE
    $classAttr = $class->getAttributes(RequireActiveTournament::class);

    // 👉 Si aucun des deux → on sort
    if (empty($methodAttr) && empty($classAttr)) {
        return;
    }

        $request = $event->getRequest();

        // --------- 1. Trouver l'ID du tournoi depuis l'URL ---------
        $tournamentId =
            $request->attributes->get('tournamentId') ??
            $request->attributes->get('id') ??
            null;

        if (!$tournamentId) {
            return; // pas un tournoi → on ignore
        }

        /** @var Tournament|null $tournament */
        $tournament = $this->tournamentRepo->find($tournamentId);

        // Cas inexistant
        if (!$tournament) {
            throw new AccessDeniedHttpException("Tournoi introuvable.");
        }

        // --------- 2. Autoriser les arbitres même si inactif ---------
        $user = $this->security->getUser();
        if ($user && $tournament->getReferees()->contains($user)) {
            return; // arbitre = accès complet
        }

        // --------- 3. Autoriser la pré-inscription même si inactif ---------
        $routeName = $request->attributes->get('_route');

        if (str_contains($routeName, 'preinscription')) {
            return;
        }

        // --------- 4. Si le tournoi n'est pas actif → bloquer ---------
        if (!$tournament->isActive()) {
            throw new AccessDeniedHttpException("Le tournoi n’est pas encore lancé.");
        }
    }
}
