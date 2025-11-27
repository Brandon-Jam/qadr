<?php

namespace App\Controller\Admin;

use App\Entity\MatchCardPlay;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class MatchCardPlayCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MatchCardPlay::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Carte jouée en match')
            ->setEntityLabelInPlural('Cartes jouées en match')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),

            AssociationField::new('match')
                ->setLabel('Match')
                ->autocomplete()
                ->setRequired(true),

            AssociationField::new('player')
                ->setLabel('Participant')
                ->autocomplete()
                ->setRequired(true),

            AssociationField::new('card')
                ->setLabel('Carte')
                ->autocomplete()
                ->setRequired(true),

            AssociationField::new('usedBy')
                ->setLabel('Utilisateur ayant utilisé la carte')
                ->autocomplete(),

            TextField::new('effectApplied')
                ->setLabel('Effet appliqué')
                ->hideOnIndex(),

            DateTimeField::new('playedAt')
                ->setLabel('Jouée à')
                ->hideOnForm(),

            DateTimeField::new('usedAt')
                ->setLabel('Utilisée à')
                ->hideOnForm()
        ];
    }
}
