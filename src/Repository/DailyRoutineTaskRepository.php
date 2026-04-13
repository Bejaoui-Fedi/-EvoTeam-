<?php

namespace App\Repository;

use App\Entity\DailyRoutineTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyRoutineTask>
 */
class DailyRoutineTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyRoutineTask::class);
    }

    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchByTitle($user, $searchTerm): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.user = :user')
            ->andWhere('t.title LIKE :search')
            ->setParameter('user', $user)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}