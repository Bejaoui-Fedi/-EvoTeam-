<?php

namespace App\Repository;

use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function findOneByUserAndEvent(User $user, int $eventId): ?Participation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('IDENTITY(p.event) = :eventId')
            ->setParameter('user', $user)
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Participation[]
     */
    public function findForListing(?User $user, bool $isAdmin): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.event', 'e')->addSelect('e')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if (!$isAdmin && $user !== null) {
            $qb->andWhere('p.user = :user')->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }
}
