<?php

namespace App\Repository;

use App\Entity\WellbeingTracker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WellbeingTracker>
 */
class WellbeingTrackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WellbeingTracker::class);
    }

    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.user', 'u')
            ->leftJoin('w.routineTask', 'rt')
            ->addSelect('u', 'rt')
            ->getQuery()
            ->getResult();
    }
}
