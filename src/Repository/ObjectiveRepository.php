<?php

namespace App\Repository;

use App\Entity\Objective;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objective>
 */
class ObjectiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objective::class);
    }

    /**
     * Returns all published objectives ordered by creation date DESC
     * @return Objective[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isPublished = :val')
            ->setParameter('val', true)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search + sort objectives (admin uses all, user uses published only)
     * @return Objective[]
     */
    public function search(string $query = '', string $sort = 'title_az', bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($publishedOnly) {
            $qb->andWhere('o.isPublished = :pub')->setParameter('pub', true);
        }

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(o.title)', ':q'),
                    $qb->expr()->like('LOWER(o.description)', ':q'),
                    $qb->expr()->like('LOWER(o.level)', ':q')
                )
            )->setParameter('q', '%' . strtolower($query) . '%');
        }

        switch ($sort) {
            case 'title_za':
                $qb->orderBy('o.title', 'DESC');
                break;
            case 'level':
                $qb->orderBy('o.level', 'ASC');
                break;
            case 'published':
                $qb->orderBy('o.isPublished', 'DESC')->addOrderBy('o.title', 'ASC');
                break;
            default: // title_az
                $qb->orderBy('o.title', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}
