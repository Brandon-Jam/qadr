<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<TournamentParticipant>
 */
class TournamentParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentParticipant::class);
    }

    public function countAlivePlayers(Tournament $tournament): int
{
    return (int) $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->where('p.tournament = :t')
        ->andWhere('p.hp > 0')
        ->setParameter('t', $tournament)
        ->getQuery()
        ->getSingleScalarResult();
}

    //    /**
    //     * @return TournamentParticipant[] Returns an array of TournamentParticipant objects
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

    //    public function findOneBySomeField($value): ?TournamentParticipant
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
