<?php

namespace App\Controller\Admin;

use App\Entity\TournamentParticipant;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

#[IsGranted('ROLE_ADMIN')]
class TournamentParticipantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TournamentParticipant::class;
    }

    /**
     * ⭐️ ACTIONS ADMIN
     */
    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', '✔️ Approuver')
            ->linkToCrudAction('approveParticipant');

        $reject = Action::new('reject', '❌ Refuser')
            ->linkToCrudAction('rejectParticipant');

        $markPaid = Action::new('markPaid', '💰 Marquer payé')
            ->linkToCrudAction('markParticipantPaid');

        return $actions
            ->add(Action::INDEX, $approve)
            ->add(Action::INDEX, $reject)
            ->add(Action::INDEX, $markPaid);
    }

    /**
     * ⭐️ FIELDS
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            AssociationField::new('user', 'Utilisateur'),
            AssociationField::new('tournament', 'Tournoi'),

            BooleanField::new('isPending', 'En attente'),
            BooleanField::new('isApproved', 'Validé'),
            BooleanField::new('isPaid', 'Payé'),

            IntegerField::new('hp', 'HP'),
            BooleanField::new('isEliminated', 'Éliminé'),

            IntegerField::new('credits', 'Crédits actuels'),
            IntegerField::new('creditsEarned', 'Crédits gagnés'),
            IntegerField::new('creditsSpent', 'Crédits dépensés'),

            DateTimeField::new('joinedAt', 'Demande le')->hideOnIndex(),

            CollectionField::new('tournamentParticipantCards', 'Cartes')
                ->hideOnForm(),

            CollectionField::new('matchInvitesSent', 'Invites envoyées')
                ->hideOnForm(),

            CollectionField::new('matchInvitesReceived', 'Invites reçues')
                ->hideOnForm(),
        ];
    }

    /**
     * ⭐️ ACTION : APPROUVER
     */
    public function approveParticipant(AdminContext $context, EntityManagerInterface $em)
    {
        $p = $context->getEntity()->getInstance();
        $p->setIsPending(false);
        $p->setIsApproved(true);

        $em->flush();

        $this->addFlash('success', 'Participant approuvé ✔️');
        return $this->redirect($context->getReferrer());
    }

    /**
     * ⭐️ ACTION : REFUSER
     */
    public function rejectParticipant(AdminContext $context, EntityManagerInterface $em)
    {
        $p = $context->getEntity()->getInstance();

        $em->remove($p);
        $em->flush();

        $this->addFlash('warning', 'Participant refusé ❌');
        return $this->redirect($context->getReferrer());
    }

    /**
     * ⭐️ ACTION : MARQUER PAYÉ
     */
    public function markParticipantPaid(AdminContext $context, EntityManagerInterface $em)
    {
        $p = $context->getEntity()->getInstance();

        $p->setIsApproved(true);
        $p->setIsPaid(true);

        $em->flush();

        $this->addFlash('success', 'Paiement confirmé 💰');
        return $this->redirect($context->getReferrer());
    }
}
