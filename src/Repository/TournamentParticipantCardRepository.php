<?php

namespace App\Repository;

use App\Entity\TournamentParticipantCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TournamentParticipantCard>
 */
class TournamentParticipantCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentParticipantCard::class);
    }

    public function getTopCardsGlobal(int $limit = 5): array
{
    return $this->createQueryBuilder('tpc')
        ->select('c.name AS card_name, COUNT(tpc.id) AS times_played')
        ->join('tpc.tournamentCard', 'tc')
        ->join('tc.card', 'c')
        ->groupBy('c.id')
        ->orderBy('times_played', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function getTopCardsByTournament(): array
{
    return $this->createQueryBuilder('tpc')
        ->select('t.name AS tournament_name, c.name AS card_name, COUNT(tpc.id) AS times_played')
        ->join('tpc.tournamentCard', 'tc')
        ->join('tc.card', 'c')
        ->join('tc.tournament', 't')
        ->groupBy('t.id, c.id')
        ->orderBy('t.name', 'ASC')
        ->addOrderBy('times_played', 'DESC')
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return TournamentParticipantCard[] Returns an array of TournamentParticipantCard objects
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

    //    public function findOneBySomeField($value): ?TournamentParticipantCard
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
