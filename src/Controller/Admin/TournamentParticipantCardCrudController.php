<?php

namespace App\Controller\Admin;

use App\Entity\TournamentParticipantCard;
use App\Entity\TournamentParticipant;
use App\Entity\Card;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

//#[IsGranted('ROLE_ADMIN')]
class TournamentParticipantCardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TournamentParticipantCard::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Carte du joueur')
            ->setEntityLabelInPlural('Cartes des joueurs')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('participant')
                ->setLabel('Participant')
                ->setRequired(true)
                ->autocomplete(),

            AssociationField::new('card')
                ->setLabel('Carte')
                ->setRequired(true)
                ->autocomplete(),

            IntegerField::new('quantity')
                ->setLabel('Quantité')
                ->setRequired(false),
        ];
    }
}
