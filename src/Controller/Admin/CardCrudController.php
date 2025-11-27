<?php

namespace App\Controller\Admin;

use App\Entity\Card;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Nom'),
            TextareaField::new('effect', 'Description')->hideOnIndex(),

            IntegerField::new('cost', 'Coût'),
            IntegerField::new('power', 'Puissance'),

            ChoiceField::new('type', 'Type')->setChoices([
                'Attaque'  => 'attack',
                'Défense'  => 'defense',
                'Soin'     => 'heal',
                'Spécial'  => 'special',
            ]),

            ChoiceField::new('trigger', 'Déclencheur')->setChoices([
                'À l\'utilisation' => 'on_use',
                'Si tu gagnes'     => 'on_win',
                'Après le match'   => 'after_match',
            ]),

            ChoiceField::new('stat', 'Stat cible')->setChoices([
                'HP'      => 'hp',
                'Dégâts'  => 'damage',
                'Bouclier'=> 'shield',
            ]),

            ChoiceField::new('operator', 'Opérateur')->setChoices([
                '+' => '+',
                '-' => '-',
            ]),

            IntegerField::new('value', 'Valeur'),

            TextField::new('element', 'Élément'),
            TextField::new('image', 'Image'),
        ];
    }
}
