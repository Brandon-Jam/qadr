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
     * Récupère les matchs d’un utilisateur dans un tournoi donné
     */
    public function findMatchesByUserAndTournament($user, $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.player1 = :user OR m.player2 = :user')
            ->setParameter('tournament', $tournament)
            ->setParameter('user', $user)
            ->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les matchs d’un participant (pour son tournoi uniquement)
     */
    public function getMatchesForParticipant(TournamentParticipant $participant): array
    {
        $user = $participant->getUser();
        $tournament = $participant->getTournament();

        return $this->createQueryBuilder('m')
            ->where('(m.player1 = :u OR m.player2 = :u)')
            ->andWhere('m.tournament = :t')
            ->setParameter('u', $user)
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de victoires du participant dans SON tournoi
     */
    public function countWinsByParticipant(TournamentParticipant $participant): int
    {
        $user = $participant->getUser();
        $tournament = $participant->getTournament();

        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.winner = :u')
            ->andWhere('m.tournament = :t')
            ->setParameter('u', $user)
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasOngoingMatch(TournamentParticipant $participant): bool
{
    return (bool) $this->createQueryBuilder('m')
        ->where('(m.player1 = :p OR m.player2 = :p)')
        ->andWhere('m.isValidated = false')
        ->andWhere('m.winner IS NULL')
        ->setParameter('p', $participant)
        ->getQuery()
        ->getOneOrNullResult();
}

public function countWinsBetween(
    TournamentParticipant $a,
    TournamentParticipant $b
): int {
    $qb = $this->createQueryBuilder('m');

    $qb->select('COUNT(m.id)')
        ->where(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->eq('m.player1', ':a'),
                    $qb->expr()->eq('m.player2', ':b'),
                    $qb->expr()->eq('m.winner', ':userA')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('m.player1', ':b'),
                    $qb->expr()->eq('m.player2', ':a'),
                    $qb->expr()->eq('m.winner', ':userB')
                )
            )
        )
        ->setParameter('a', $a)
        ->setParameter('b', $b)
        ->setParameter('userA', $a->getUser())
        ->setParameter('userB', $b->getUser());

    return (int) $qb->getQuery()->getSingleScalarResult();
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

