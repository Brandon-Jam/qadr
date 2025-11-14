<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\TournamentCard;
use App\Entity\TournamentParticipant;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // ---------------------------------------------------------
        // 1) Création des 4 CARTES OFFICIELLES
        // ---------------------------------------------------------
        $cardsData = [
            [
                'name' => 'Boule de feu',
                'effect' => 'Inflige 1 dégât supplémentaire en cas de victoire',
                'cost' => 4,
                'power' => 1,
                'image' => 'feu1.png',
                'element' => 'feu',
                'type' => 'attack',
                'trigger' => 'on_win',
                'stat' => 'hp',
                'operator' => '-',
                'value' => 1,
            ],
            [
                'name' => 'Bouclier magique',
                'effect' => 'Réduit les dégâts subis de 1',
                'cost' => 3,
                'power' => 1,
                'image' => 'eau1.png',
                'element' => 'eau',
                'type' => 'defense',
                'trigger' => 'on_damage',
                'stat' => 'damage',
                'operator' => '-',
                'value' => 1,
            ],
            [
                'name' => 'Potion de soin',
                'effect' => 'Rend 1 HP',
                'cost' => 2,
                'power' => 1,
                'image' => 'terre1.png',
                'element' => 'terre',
                'type' => 'heal',
                'trigger' => 'on_use',
                'stat' => 'hp',
                'operator' => '+',
                'value' => 1,
            ],
            [
                'name' => 'Contresort',
                'effect' => 'Annule une carte adverse',
                'cost' => 1,
                'power' => 1,
                'image' => 'air1.png',
                'element' => 'air',
                'type' => 'special',
                'trigger' => 'on_use',
                'stat' => 'negate',
                'operator' => '+',
                'value' => 1,
            ],
        ];

        $cards = [];

        foreach ($cardsData as $data) {
            $card = new Card();
            $card->setName($data['name']);
            $card->setEffect($data['effect']);
            $card->setCost($data['cost']);
            $card->setPower($data['power']);
            $card->setImage($data['image']);
            $card->setElement($data['element']);
            $card->setType($data['type']);
            $card->setTrigger($data['trigger']);
            $card->setStat($data['stat']);
            $card->setOperator($data['operator']);
            $card->setValue($data['value']);

            $manager->persist($card);
            $cards[] = $card;
        }

        $manager->flush();


        // ---------------------------------------------------------
        // 2) Création de 3 TOURNOIS
        // ---------------------------------------------------------
        $images = [];
        for ($i = 1; $i <= 10; $i++) {
            $images[] = "t$i.jpg";
        }

        $tournaments = [];

        for ($i = 1; $i <= 3; $i++) {
            $t = new Tournament();
            $t->setName("Tournoi #$i");
            $t->setLocation("Lieu $i");
            $t->setPrice(rand(10, 20));
            $t->setWinningPrice(rand(100, 200));
            $t->setAvailableSlots(32);
            $t->setImage($images[array_rand($images)]);
            $t->setDate(new \DateTimeImmutable('+' . rand(3, 15) . ' days'));
            $t->setStatus('Open');
            $t->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($t);
            $tournaments[] = $t;
        }

        $manager->flush();


        // ---------------------------------------------------------
        // 3) Lier les 4 cartes à CHAQUE tournoi (TournamentCard)
        // ---------------------------------------------------------
        foreach ($tournaments as $t) {
            foreach ($cards as $card) {
                $tc = new TournamentCard();
                $tc->setTournament($t);
                $tc->setCard($card);
                $manager->persist($tc);
            }
        }

        $manager->flush();


        // ---------------------------------------------------------
        // 4) Création de 10 JOUEURS
        // ---------------------------------------------------------
        $avatars = [];
        for ($i = 1; $i <= 20; $i++) {
            $avatars[] = "avatar$i.png";
        }

        $players = [];

        for ($i = 1; $i <= 10; $i++) {
            $u = new User();
            $u->setEmail("player$i@test.com");
            $u->setRoles(["ROLE_USER"]);
            $u->setPassword($this->passwordHasher->hashPassword($u, "password"));
            $u->setPseudo("Player$i");
            $u->setAvatar($avatars[array_rand($avatars)]);

            $manager->persist($u);
            $players[] = $u;
        }

        $manager->flush();


        // ---------------------------------------------------------
        // 5) Inscrire les joueurs aléatoirement aux tournois
        // ---------------------------------------------------------
        foreach ($players as $user) {
            foreach ($tournaments as $t) {
                if (rand(0, 1)) { // 50% chance
                    $p = new TournamentParticipant();
                    $p->setUser($user);
                    $p->setTournament($t);
                    $p->setJoinedAt(new \DateTimeImmutable());
                    $p->setConfirmed(true);
                    $p->setCredits(0);
                    $p->setHp(10);            // tu peux adapter
                    $p->setIsEliminated(false);

                    $manager->persist($p);
                }
            }
        }

        $manager->flush();


        // ---------------------------------------------------------
        // 6) Ajouter 2 ARBITRES (pas inscrits aux tournois)
        // ---------------------------------------------------------
        for ($i = 1; $i <= 2; $i++) {
            $ref = new User();
            $ref->setEmail("ref$i@test.com");
            $ref->setRoles(["ROLE_REFEREE"]);
            $ref->setPassword($this->passwordHasher->hashPassword($ref, "password"));
            $ref->setPseudo("Referee$i");
            $ref->setAvatar($avatars[array_rand($avatars)]);

            $manager->persist($ref);
        }

        $manager->flush();
    }
}
