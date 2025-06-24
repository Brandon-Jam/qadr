<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\TournamentCard;
use App\Entity\TournamentParticipant;
use Doctrine\Persistence\ObjectManager;
use App\Entity\TournamentParticipantCard;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {

        // Création d’un tournoi
        $tournament = new Tournament();
        $tournament->setName('Tournoi Alpha');
        $tournament->setLocation('On verra');
        $tournament->setPrice(12);
        $tournament->setWinningPrice(50);
        $tournament->setAvailableSlots(8);
        $tournament->setDate(new \DateTimeImmutable('+7 days'));
        $tournament->setStatus('Open');
        $tournament->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($tournament);

             // Cartes globales
        // --- Création de 3 cartes
    $cardData = [
        ['name' => 'Boule de feu', 'effect' => 'Inflige 20 dégâts', 'cost' => 4, 'power' => 1],
        ['name' => 'Bouclier magique', 'effect' => 'Réduit dégâts de 10%', 'cost' => 3, 'power' => 1],
        ['name' => 'Potion de soin', 'effect' => 'Rend 15 PV', 'cost' => 2, 'power' => 1]
    ];
    $cards = [];
    foreach ($cardData as $data) {
        $card = new Card();
        $card->setName($data['name']);
        $card->setEffect($data['effect']);
        $card->setCost($data['cost']);
        $card->setPower($data['power']);
        $card->setImage('https://via.placeholder.com/150'); // image générique
        $manager->persist($card);
        $cards[] = $card;
    }

    // --- Création de 3 tournois
    $tournaments = [];
    for ($i = 1; $i <= 3; $i++) {
        $tournament = new Tournament();
        $tournament->setName("Tournoi #$i");
        $tournament->setLocation("Ville $i");
        $tournament->setPrice(rand(5, 20));
        $tournament->setWinningPrice(rand(30, 100));
        $tournament->setAvailableSlots(rand(8, 16));
        $tournament->setDate(new \DateTimeImmutable('+' . rand(3, 15) . ' days'));
        $tournament->setStatus('Open');
        $tournament->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($tournament);
        $tournaments[] = $tournament;

        // Lier cartes au tournoi via TournamentCard
        foreach ($cards as $card) {
            $tc = new TournamentCard();
            $tc->setTournament($tournament);
            $tc->setCard($card);
            $manager->persist($tc);
        }
    }


       // --- Création des 10 utilisateurs
    for ($i = 1; $i <= 10; $i++) {
        $user = new User();
        $user->setEmail("player$i@test.com");
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // Inscription aléatoire à 1 ou plusieurs tournois
        foreach ($tournaments as $tournament) {
            if (rand(0, 1)) { // 50% chance de s'inscrire
                $participant = new TournamentParticipant();
                $participant->setUser($user);
                $participant->setTournament($tournament);
                $participant->setJoinedAt(new \DateTimeImmutable());
                $participant->setConfirmed(true);
                $participant->setCredits(rand(5, 15));
                $manager->persist($participant);

                // Donner des cartes aléatoires
                foreach ($cards as $card) {
                    if (rand(0, 1)) {
                        $tpc = new TournamentParticipantCard();
                        $tpc->setParticipant($participant);
                        $tpc->setCard($card);
                        $tpc->setQuantity(rand(1, 3));
                        $manager->persist($tpc);
                    }
                }
            }
        }
    }

        $manager->flush();
    }
}