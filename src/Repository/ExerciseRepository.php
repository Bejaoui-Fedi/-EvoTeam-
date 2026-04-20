<?php

namespace App\Repository;

use App\Entity\Exercise;
use App\Entity\Objective;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
class ExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    /**
     * Returns published exercises for a specific objective ordered by duration ASC
     * @return Exercise[]
     */
    public function findByObjectivePublished(Objective $objective): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.objective = :obj')
            ->andWhere('e.isPublished = :pub')
            ->setParameter('obj', $objective)
            ->setParameter('pub', true)
            ->orderBy('e.durationMinutes', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search + sort exercises for a given objective (admin: all / user: published)
     * @return Exercise[]
     */
    public function searchByObjective(Objective $objective, string $query = '', string $sort = 'title_az', bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.objective = :obj')
            ->setParameter('obj', $objective);

        if ($publishedOnly) {
            $qb->andWhere('e.isPublished = :pub')->setParameter('pub', true);
        }

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.title)', ':q'),
                    $qb->expr()->like('LOWER(e.description)', ':q'),
                    $qb->expr()->like('LOWER(e.type)', ':q'),
                    $qb->expr()->like('LOWER(e.difficulty)', ':q')
                )
            )->setParameter('q', '%' . strtolower($query) . '%');
        }

        switch ($sort) {
            case 'title_za':
                $qb->orderBy('e.title', 'DESC');
                break;
            case 'duration':
                $qb->orderBy('e.durationMinutes', 'ASC');
                break;
            case 'difficulty':
                $qb->orderBy('e.difficulty', 'ASC');
                break;
            case 'published':
                $qb->orderBy('e.isPublished', 'DESC')->addOrderBy('e.title', 'ASC');
                break;
            default: // title_az
                $qb->orderBy('e.title', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search all exercises with filters (admin global view)
     * @return Exercise[]
     */
    public function searchAll(string $query = '', string $sort = 'title_az'): array
    {
        $qb = $this->createQueryBuilder('e')->leftJoin('e.objective', 'o');

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.title)', ':q'),
                    $qb->expr()->like('LOWER(e.description)', ':q'),
                    $qb->expr()->like('LOWER(e.type)', ':q'),
                    $qb->expr()->like('LOWER(e.difficulty)', ':q'),
                    $qb->expr()->like('LOWER(o.title)', ':q')
                )
            )->setParameter('q', '%' . strtolower($query) . '%');
        }

        switch ($sort) {
            case 'title_za':
                $qb->orderBy('e.title', 'DESC');
                break;
            case 'duration':
                $qb->orderBy('e.durationMinutes', 'ASC');
                break;
            case 'difficulty':
                $qb->orderBy('e.difficulty', 'ASC');
                break;
            default:
                $qb->orderBy('e.title', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}
