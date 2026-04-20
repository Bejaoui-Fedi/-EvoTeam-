<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findByFilters(?User $user, ?string $search, string $sort, bool $isAdmin): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.event', 'e')
            ->leftJoin('r.author', 'a')
            ->addSelect('e', 'a');

        if (!$isAdmin && $user !== null) {
            $qb->andWhere('r.author = :user')->setParameter('user', $user);
        }

        if (!empty($search)) {
            $qb->andWhere('r.title LIKE :q OR r.comment LIKE :q OR e.name LIKE :q OR a.nom LIKE :q')
                ->setParameter('q', '%' . trim($search) . '%');
        }

        switch ($sort) {
            case 'rating_asc':
                $qb->orderBy('r.rating', 'ASC');
                break;
            case 'rating_desc':
                $qb->orderBy('r.rating', 'DESC');
                break;
            case 'title_asc':
                $qb->orderBy('r.title', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('r.title', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('r.reviewDate', 'ASC');
                break;
            default:
                $qb->orderBy('r.reviewDate', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function getStats(?User $user, bool $isAdmin): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS totalReviews')
            ->addSelect('AVG(r.rating) AS avgRating')
            ->addSelect('COUNT(DISTINCT IDENTITY(r.event)) AS totalEventsRated');

        if (!$isAdmin && $user !== null) {
            $qb->andWhere('r.author = :user')->setParameter('user', $user);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalReviews' => (int) ($result['totalReviews'] ?? 0),
            'avgRating' => round((float) ($result['avgRating'] ?? 0), 2),
            'totalEventsRated' => (int) ($result['totalEventsRated'] ?? 0),
        ];
    }
}
