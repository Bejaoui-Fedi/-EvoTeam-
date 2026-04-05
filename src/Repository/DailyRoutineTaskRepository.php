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
}
