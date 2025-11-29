<?php

namespace App\Controller\Admin;

use App\Entity\Tournament;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

#[IsGranted('ROLE_ADMIN')]
class TournamentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tournament::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // ID
            IdField::new('id')->onlyOnIndex(),

            // Infos générales
            TextField::new('name', 'Nom du tournoi'),
            TextField::new('location', 'Lieu'),

            DateTimeField::new('date', 'Date du tournoi')
                ->setRequired(true),

            ChoiceField::new('status', 'Statut')->setChoices([
                'Ouvert' => 'open',
                'Fermé' => 'closed',
                'Terminé' => 'finished',
                'En cours' => 'in_progress',
            ]),

            // Capacités
            IntegerField::new('availableSlots', 'Places disponibles'),

            // Prix & récompenses
            MoneyField::new('price', 'Prix d’entrée')
                ->setCurrency('EUR'),

            MoneyField::new('winningPrice', 'Cash prize')
                ->setCurrency('EUR'),

            // Image
            ImageField::new('image', 'Image du tournoi')
                ->setBasePath('img/tournaments')        // URL publique
                ->setUploadDir('public/img/tournaments') // Dossier serveur
                ->setRequired(false)
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]'),

            // Tournois Actif ?
            BooleanField::new('active', 'Tournoi actif ?')
                ->renderAsSwitch(true)
                ->setHelp('Active = le tournoi est lancé et toutes les fonctionnalités seront débloquées.'),


            // Arbitres
            AssociationField::new('referees', 'Arbitres')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->setRequired(false),

            // Infos internes
            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),
        ];
    }
}
