<?php

namespace App\Controller\Admin;

use App\Entity\TournamentCard;
use App\Entity\Tournament;
use App\Entity\Card;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TournamentCardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TournamentCard::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Carte du Tournoi')
            ->setEntityLabelInPlural('Cartes du Tournoi')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('tournament')
                ->setLabel('Tournoi')
                ->setRequired(true),

            AssociationField::new('card')
                ->setLabel('Carte RPG')
                ->setRequired(true)
                ->autocomplete()
        ];
    }
}
