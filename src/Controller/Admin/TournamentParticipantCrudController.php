<?php

namespace App\Controller\Admin;

use App\Entity\TournamentParticipant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class TournamentParticipantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TournamentParticipant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            AssociationField::new('user')
                ->setLabel('Utilisateur'),

            AssociationField::new('tournament')
                ->setLabel('Tournoi'),

            BooleanField::new('confirmed')
                ->setLabel('Confirmé'),

            DateTimeField::new('joinedAt')
                ->setLabel('Inscrit le')
                ->hideOnIndex(),

            IntegerField::new('hp')
                ->setLabel('HP'),

            BooleanField::new('isEliminated')
                ->setLabel('Éliminé'),

            IntegerField::new('credits')
                ->setLabel('Crédits actuels'),

            IntegerField::new('creditsEarned')
                ->setLabel('Crédits gagnés'),

            IntegerField::new('creditsSpent')
                ->setLabel('Crédits dépensés'),

            // Relations utilitaires (affichables mais pas éditables)
            CollectionField::new('tournamentParticipantCards')
                ->setLabel('Cartes possédées')
                ->hideOnForm(),

            CollectionField::new('matchInvitesSent')
                ->setLabel('Invitations envoyées')
                ->hideOnForm(),

            CollectionField::new('matchInvitesReceived')
                ->setLabel('Invitations reçues')
                ->hideOnForm(),
        ];
    }
}
