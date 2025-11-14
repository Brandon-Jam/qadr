<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class TournamentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tournament::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Nom du tournoi'),
            TextField::new('location', 'Lieu'),

            IntegerField::new('availableSlots', 'Places disponibles'),
            
            IntegerField::new('price', 'Prix d’entrée (€)'),

            IntegerField::new('winningPrice', 'Cash prize (€)'),

            DateTimeField::new('date', 'Date du tournoi')
                ->setRequired(true),

            ChoiceField::new('status', 'Statut')->setChoices([
                'Ouvert' => 'open',
                'Fermé' => 'closed',
                'Terminé' => 'finished',
                'En cours' => 'in_progress',
            ]),

            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),

            TextField::new('image', 'Image (URL ou chemin)'),

            AssociationField::new('referees', 'Arbitres')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->setRequired(false),
        ];
    }
}
