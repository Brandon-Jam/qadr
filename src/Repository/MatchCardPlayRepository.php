<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\MatchCardPlay;
use App\Entity\TournamentParticipant;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MatchCardPlay>
 */
class MatchCardPlayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchCardPlay::class);
    }

    public function getTopCardsGlobal(int $limit = 5): array
{
    return $this->createQueryBuilder('mcp')
        ->select('c.name AS card_name, COUNT(mcp.id) AS times_played')
        ->join('mcp.card', 'c')
        ->groupBy('c.id')
        ->orderBy('times_played', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function getTopCardsByTournament(): array
{
    return $this->createQueryBuilder('mcp')
        ->select('t.name AS tournament_name, c.name AS card_name, COUNT(mcp.id) AS times_played')
        ->join('mcp.card', 'c')
        ->join('mcp.match', 'm')
        ->join('m.tournament', 't')
        ->groupBy('t.id, c.id')
        ->orderBy('t.name', 'ASC')
        ->addOrderBy('times_played', 'DESC')
        ->getQuery()
        ->getResult();
}
public function findMostUsedElement(TournamentParticipant $participant): ?string
{
    return $this->createQueryBuilder('u')
        ->select('c.element')
        ->join('u.card', 'c')
        ->where('u.participant = :p')
        ->setParameter('p', $participant)
        ->groupBy('c.element')
        ->orderBy('COUNT(u.id)', 'DESC')
        ->setMaxResults(1)
       ->getQuery()
        ->getOneOrNullResult();
}

public function findMostUsedCard(TournamentParticipant $participant): ?Card
{
    return $this->createQueryBuilder('u')
        ->select('c')
        ->join('u.card', 'c')
        ->where('u.participant = :p')
        ->setParameter('p', $participant)
        ->groupBy('c.id')
        ->orderBy('COUNT(u.id)', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
    //    /**
    //     * @return MatchCardPlay[] Returns an array of MatchCardPlay objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MatchCardPlay
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
