<?php

namespace App\Controller;
use App\Entity\Card;
use App\Entity\Tournament;
use App\Entity\TournamentCard;
use App\Entity\TournamentParticipant;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TournamentParticipantCard;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\TournamentMatchRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\TournamentParticipantRepository;
use App\Repository\TournamentParticipantCardRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TournamentController extends AbstractController
{
    #[Route('/tournament', name: 'app_tournament')]
  

public function index(
    TournamentRepository $tournamentRepo,
    Security $security
): Response {
    $user = $security->getUser();
    $tournaments = $tournamentRepo->findAll();

    $registeredTournaments = [];

   if ($user !== null) {
    foreach ($tournaments as $tournament) {
        foreach ($tournament->getTournamentParticipants() as $participant) {
            if ($participant->getUser() && $participant->getUser()->getId() === $user->getId()) {
                $registeredTournaments[] = $tournament->getId();
            }
        }
    }
}

    return $this->render('tournament/index.html.twig', [
        'tournaments' => $tournaments,
        'registeredTournaments' => $registeredTournaments,
    ]);
}





#[Route('/tournament/{id}', name: 'app_tournament_show')]
public function show(Tournament $tournament): Response
{

    return $this->render('tournament/show.html.twig', [
        'tournament' => $tournament,
    ]);
}


#[Route('/tournament/{id}/inscription', name: 'app_tournament_register')]
public function registerToTournament(
    Tournament $tournament,
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();

    
    // 🚫 Empêche les arbitres de s'inscrire
    if (in_array('ROLE_REFEREE', $user->getRoles())) {
        $this->addFlash('danger', 'Un arbitre ne peut pas participer à un tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }

    // ✅ Vérifie si déjà inscrit
    $existing = $em->getRepository(TournamentParticipant::class)->findOneBy([
        'user' => $user,
        'tournament' => $tournament
    ]);

    if ($existing) {
        $this->addFlash('info', 'Vous êtes déjà inscrit à ce tournoi.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }


    $participant = new TournamentParticipant();
    $participant->setUser($user);
    $participant->setTournament($tournament);
    $participant->setJoinedAt(new \DateTimeImmutable());
    $participant->setHp(10);              // HP de départ
    $participant->setIsEliminated(false);
    $participant->setConfirmed(true);
    $em->persist($participant);
    $em->flush();

    $this->addFlash('success', 'Inscription réussie !');

    return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
}

#[Route('/tournament/{id}/shop', name: 'app_tournament_shop')]
public function shop(Tournament $tournament, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    
    // Récupère le TournamentParticipant du joueur
    $participant = $em->getRepository(TournamentParticipant::class)
        ->findOneBy(['user' => $user, 'tournament' => $tournament]);

    if (!$participant) {
        $this->addFlash('error', 'Vous devez être inscrit à ce tournoi pour accéder à la boutique.');
        return $this->redirectToRoute('app_tournament_show', ['id' => $tournament->getId()]);
    }

    // Récupère les cartes disponibles pour ce tournoi
    $availableCards = $em->getRepository(TournamentCard::class)
        ->findBy(['tournament' => $tournament]);

    return $this->render('tournament/shop.html.twig', [
        'tournament' => $tournament,
        'participant' => $participant,
        'cards' => $availableCards,
    ]);
}

#[Route('/tournament/{id}/buy/{cardId}', name: 'app_tournament_buy_card', methods: ['POST'])]
public function buyCard(
    Tournament $tournament,
    int $cardId,
    EntityManagerInterface $em
): Response {
    $user = $this->getUser();

    $participant = $em->getRepository(TournamentParticipant::class)
        ->findOneBy(['user' => $user, 'tournament' => $tournament]);

    if (!$participant) {
        $this->addFlash('error', 'Action non autorisée.');
        return $this->redirectToRoute('app_tournament_shop', ['id' => $tournament->getId()]);
    }

    $card = $em->getRepository(Card::class)->find($cardId);

    if (!$card) {
        $this->addFlash('error', 'Carte introuvable.');
        return $this->redirectToRoute('app_tournament_shop', ['id' => $tournament->getId()]);
    }

    if ($participant->getCredits() < $card->getCost()) {
        $this->addFlash('error', 'Pas assez de crédits.');
        return $this->redirectToRoute('app_tournament_shop', ['id' => $tournament->getId()]);
    }

    // Vérifie si le joueur a déjà cette carte pour ce tournoi
    $tpc = $em->getRepository(TournamentParticipantCard::class)
        ->findOneBy(['participant' => $participant, 'card' => $card]);

    if ($tpc) {
        $tpc->setQuantity($tpc->getQuantity() + 1);
    } else {
        $tpc = new TournamentParticipantCard();
        $tpc->setParticipant($participant);
        $tpc->setCard($card);
        $tpc->setQuantity(1);
        $em->persist($tpc);
    }

    // Déduire les crédits
    $participant->setCredits($participant->getCredits() - $card->getCost());

    $em->flush();

    $this->addFlash('success', 'Carte achetée avec succès !');
    return $this->redirectToRoute('app_tournament_shop', ['id' => $tournament->getId()]);
}

#[Route('/tournament/{id}/inventaire', name: 'app_tournament_inventory')]
public function inventory(
    Tournament $tournament,
    TournamentParticipantRepository $participantRepo
): Response {
    /** @var User $user */
    $user = $this->getUser();

    // Vérifie que l'utilisateur est bien inscrit à ce tournoi
    $participant = $participantRepo->findOneBy([
        'user' => $user,
        'tournament' => $tournament,
    ]);

    if (!$participant && !in_array('ROLE_REFEREE', $user->getRoles())) {
    $this->addFlash('danger', 'Accès refusé : vous devez être joueur ou arbitre pour voir ce match.');
    return $this->redirectToRoute('app_tournament_show', [
        'id' => $tournamentId,
    ]);
}

    // Récupère les cartes possédées
    $cards = $participant->getTournamentParticipantCards();
 
    return $this->render('tournament/inventory.html.twig', [
        'tournament' => $tournament,
        'participant' => $participant,
        'cards' => $cards,
    ]);
}
   #[Route('/tournament/{id}/profil', name: 'app_tournament_profil')]
public function profil(
    Tournament $tournament,
    TournamentParticipantRepository $participantRepo,
    TournamentMatchRepository $matchRepo,
    TournamentParticipantCardRepository $cardUsageRepo,
    Security $security
): Response {
    $user = $security->getUser();

    // Stats par défaut
    $playerStats = [
        'matchesPlayed' => 0,
        'wins' => 0,
        'losses' => 0,
        'winRate' => 0,
        'creditsEarned' => 0,
        'creditsSpent' => 0,
        'cardsUsed' => 0,
        'topCard' => null,
    ];

    $participant = null;
    $matches = [];

    if ($user) {
        // Récupérer la participation du joueur dans ce tournoi
        $participant = $participantRepo->findOneBy([
            'tournament' => $tournament,
            'user' => $user
        ]);

        if ($participant) {
            // --- Matchs du joueur ---
           $matches = $matchRepo->getMatchesForParticipant($participant);
            $matchesPlayed = count($matches);
            $wins = $matchRepo->countWinsByParticipant($participant);
            $losses = max(0, $matchesPlayed - $wins);
            $winRate = $matchesPlayed > 0 ? round(($wins / $matchesPlayed) * 100, 1) : 0;

            // --- Crédits ---
            $creditsEarned = method_exists($participant, 'getCreditsEarned') ? $participant->getCreditsEarned() : 0;
            $creditsSpent = method_exists($participant, 'getCreditsSpent') ? $participant->getCreditsSpent() : 0;

            // --- Cartes utilisées ---
            $cardsUsed = $cardUsageRepo->count(['participant' => $participant]);

            // --- Tableau final des stats ---
            $playerStats = [
                'matchesPlayed' => $matchesPlayed,
                'wins' => $wins,
                'losses' => $losses,
                'winRate' => $winRate,
                'creditsEarned' => $creditsEarned,
                'creditsSpent' => $creditsSpent,
                'cardsUsed' => $cardsUsed,
            ];
        }
    }

    return $this->render('tournament/profil.html.twig', [
        'tournament' => $tournament,
        'participant' => $participant,
        'playerStats' => $playerStats,
        'matches' => $matches,
    ]);
}
}

