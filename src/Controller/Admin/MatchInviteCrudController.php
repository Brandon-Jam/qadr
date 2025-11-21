<?php

namespace App\Controller\Admin;

use App\Entity\MatchInvite;
use App\Entity\TournamentParticipant;
use App\Entity\Tournament;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

//#[IsGranted('ROLE_ADMIN')]
class MatchInviteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchInvite::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Invitation')
            ->setEntityLabelInPlural('Invitations')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            AssociationField::new('tournament')
                ->setLabel('Tournoi')
                ->autocomplete()
                ->setRequired(true),

            AssociationField::new('challenger')
                ->setLabel('Challenger')
                ->autocomplete(),

            AssociationField::new('opponent')
                ->setLabel('Adversaire')
                ->autocomplete(),

            ChoiceField::new('status')
                ->setLabel('Statut')
                ->setChoices([
                    'En attente' => 'pending',
                    'Acceptée' => 'accepted',
                    'Refusée' => 'refused',
                ]),

            DateTimeField::new('createdAt')
                ->setLabel('Créée le')
                ->hideOnForm(),
        ];
    }
}
