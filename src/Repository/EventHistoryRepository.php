<?php

namespace App\Repository;

use App\Entity\EventHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventHistory>
 */
class EventHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventHistory::class);
    }

    /**
     * @return EventHistory[]
     */
    public function findLatest(?string $entityType = null, ?string $action = null): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.performedBy', 'u')->addSelect('u')
            ->leftJoin('h.event', 'e')->addSelect('e')
            ->orderBy('h.createdAt', 'DESC');

        if (!empty($entityType)) {
            $qb->andWhere('h.entityType = :entityType')->setParameter('entityType', $entityType);
        }

        if (!empty($action)) {
            $qb->andWhere('h.action = :action')->setParameter('action', $action);
        }

        return $qb->setMaxResults(200)->getQuery()->getResult();
    }
}
