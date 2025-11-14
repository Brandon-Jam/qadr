<?php

namespace App\Repository;

use App\Entity\TournamentMatch;
use App\Entity\TournamentParticipant;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<TournamentMatch>
 */
class TournamentMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentMatch::class);
    }

    /**
     * Récupère les matchs d’un utilisateur (User) dans un tournoi donné
     * On joint player1.user et player2.user
     */
    public function findMatchesByUserAndTournament($user, $tournament): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb
            ->leftJoin('m.player1', 'p1')
            ->leftJoin('m.player2', 'p2')
            ->where('m.tournament = :tournament')
            ->andWhere($qb->expr()->orX(
                'p1.user = :user',
                'p2.user = :user'
            ))
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les matchs d’un participant (dans son tournoi)
     */
    public function getMatchesForParticipant(TournamentParticipant $participant): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb
            ->where('m.tournament = :tournament')
            ->andWhere($qb->expr()->orX(
                'm.player1 = :p',
                'm.player2 = :p'
            ))
            ->setParameter('tournament', $participant->getTournament())
            ->setParameter('p', $participant)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de victoires d’un participant dans SON tournoi
     */
    public function countWinsByParticipant(TournamentParticipant $participant): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.winner = :p')
            ->andWhere('m.tournament = :tournament')
            ->setParameter('p', $participant)
            ->setParameter('tournament', $participant->getTournament())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * True si le participant a au moins un match NON TERMINÉ
     */
    public function hasOngoingMatch(TournamentParticipant $participant): bool
    {
        return (bool) $this->createQueryBuilder('m')
            ->where('m.isFinished = false')
            ->andWhere('(m.player1 = :p OR m.player2 = :p)')
            ->setParameter('p', $participant)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Nombre total de victoires entre deux participants (dans les deux sens)
     * Sert à limiter à 2 victoires max dans tes règles
     */
    public function countWinsBetween(
    TournamentParticipant $a,
    TournamentParticipant $b
): int {
    $qb = $this->createQueryBuilder('m');

    $pairCondition = $qb->expr()->orX(
        $qb->expr()->andX(
            $qb->expr()->eq('m.player1', ':a'),
            $qb->expr()->eq('m.player2', ':b')
        ),
        $qb->expr()->andX(
            $qb->expr()->eq('m.player1', ':b'),
            $qb->expr()->eq('m.player2', ':a')
        )
    );

    return (int) $qb
        ->select('COUNT(m.id)')
        ->where('m.tournament = :tournament')
        ->andWhere($pairCondition)
        ->andWhere('m.winner IS NOT NULL')
        ->setParameter('a', $a)
        ->setParameter('b', $b)
        ->setParameter('tournament', $a->getTournament())
        ->getQuery()
        ->getSingleScalarResult();
}

    /**
     * Match NON TERMINÉ entre deux participants (ou null)
     */
    public function getOngoingMatchBetween(TournamentParticipant $p1, TournamentParticipant $p2): ?TournamentMatch
{
    $qb = $this->createQueryBuilder('m');

    $pairCondition = $qb->expr()->orX(
        $qb->expr()->andX(
            $qb->expr()->eq('m.player1', ':p1'),
            $qb->expr()->eq('m.player2', ':p2')
        ),
        $qb->expr()->andX(
            $qb->expr()->eq('m.player1', ':p2'),
            $qb->expr()->eq('m.player2', ':p1')
        )
    );

    return $qb
        ->where('m.isFinished = false')
        ->andWhere('m.tournament = :tournament')
        ->andWhere($pairCondition)
        ->setParameter('p1', $p1)
        ->setParameter('p2', $p2)
        ->setParameter('tournament', $p1->getTournament())
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
    /**
     * Match NON TERMINÉ pour un participant (s’il en a un)
     */
    public function findActiveMatchForParticipant(TournamentParticipant $participant): ?TournamentMatch
{
    return $this->createQueryBuilder('m')
        ->where('m.isFinished = false')
        ->andWhere('m.tournament = :tournament')
        ->andWhere('(
            m.player1 = :p 
            OR 
            m.player2 = :p
        )')
        ->setParameter('p', $participant)
        ->setParameter('tournament', $participant->getTournament())
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
}


    //    /**
    //     * @return TournamentMatch[] Returns an array of TournamentMatch objects
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

    //    public function findOneBySomeField($value): ?TournamentMatch
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

