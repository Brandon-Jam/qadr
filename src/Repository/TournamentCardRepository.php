<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\TournamentCard;
use App\Entity\TournamentMatch;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<TournamentCard>
 */
class TournamentCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentCard::class);
    }

    //    /**
    //     * @return TournamentCard[] Returns an array of TournamentCard objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TournamentCard
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAvailableCardsForUserInMatch(TournamentMatch $match, User $user): array
{
    $qb = $this->createQueryBuilder('tc')
        ->join('tc.card', 'c')
        ->join('tc.tournament', 't')
        ->where('tc.tournament = :tournament')
        ->setParameter('tournament', $match->getTournament());

    // Optionnel : filtrer selon le joueur (ex : niveau, type de carte autorisée, etc.)
    // Tu peux ici ajouter une logique personnalisée selon le système de cartes

    return $qb->getQuery()->getResult();
}
}
