<?php

namespace App\Controller\Admin;

use App\Entity\TournamentMatch;
use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use App\Controller\Admin\TournamentParticipantCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TournamentMatchCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TournamentMatch::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('tournament')
                ->setRequired(true)
                ->setLabel('Tournoi'),

          AssociationField::new('player1')
    ->setLabel('Joueur 1')
    ->setCrudController(TournamentParticipantCrudController::class),

AssociationField::new('player2')
    ->setLabel('Joueur 2')
    ->setCrudController(TournamentParticipantCrudController::class),

            ChoiceField::new('phase')
                ->setLabel('Phase')
                ->setChoices([
                    'Cartes' => 'cards',
                    'Combat' => 'battle',
                    'Validé' => 'validated',
                ]),

            IntegerField::new('score1')->hideOnIndex(),
            IntegerField::new('score2')->hideOnIndex(),

            IntegerField::new('round')
                ->setLabel('Round')
                ->hideOnIndex(),

            BooleanField::new('player1Ready')
                ->setLabel('J1 prêt')
                ->renderAsSwitch(false),

            BooleanField::new('player2Ready')
                ->setLabel('J2 prêt')
                ->renderAsSwitch(false),

            BooleanField::new('isValidated')
                ->setLabel('Match validé'),

            BooleanField::new('isFinished')
                ->setLabel('Match terminé'),

           AssociationField::new('winner')
    ->setLabel('Vainqueur')
    ->setCrudController(TournamentParticipantCrudController::class)
    ->autocomplete(),

AssociationField::new('loser')
    ->setLabel('Perdant')
    ->setCrudController(TournamentParticipantCrudController::class)
    ->autocomplete(),

            DateTimeField::new('startTime')
                ->setLabel('Début')
                ->setRequired(true),

            DateTimeField::new('createdAt')
                ->setLabel('Créé le')
                ->hideOnForm(),
        ];
    }
}

