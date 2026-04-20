<?php

namespace App\Repository;

use App\Entity\ExerciseCompletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExerciseCompletion>
 *
 * @method ExerciseCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExerciseCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExerciseCompletion[]    findAll()
 * @method ExerciseCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExerciseCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseCompletion::class);
    }

    /**
     * Finds total completions for a user
     */
    public function countCompletionsByUser(int $userId): int
    {
        return $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gets completions grouped by date for the last 7 days
     */
    public function getRecentActivity(int $userId, int $days = 7): array
    {
        $date = new \DateTimeImmutable("-$days days");

        return $this->createQueryBuilder('c')
            ->select('SUBSTRING(c.completedAt, 1, 10) as day, count(c.id) as total')
            ->where('c.user = :userId')
            ->andWhere('c.completedAt >= :date')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date)
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
