<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findByFilters(?string $search, string $sort): array
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($search)) {
            $qb->andWhere('e.name LIKE :q OR e.location LIKE :q OR e.description LIKE :q')
                ->setParameter('q', '%' . trim($search) . '%');
        }

        switch ($sort) {
            case 'name_asc':
                $qb->orderBy('e.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('e.name', 'DESC');
                break;
            case 'fee_asc':
                $qb->orderBy('e.fee', 'ASC');
                break;
            case 'fee_desc':
                $qb->orderBy('e.fee', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('e.startDate', 'ASC');
                break;
            default:
                $qb->orderBy('e.startDate', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function getStats(): array
    {
        $result = $this->createQueryBuilder('e')
            ->leftJoin('e.reviews', 'r')
            ->select('COUNT(DISTINCT e.id) AS totalEvents')
            ->addSelect('COUNT(r.id) AS totalReviews')
            ->addSelect('AVG(r.rating) AS avgRating')
            ->getQuery()
            ->getSingleResult();

        return [
            'totalEvents' => (int) ($result['totalEvents'] ?? 0),
            'totalReviews' => (int) ($result['totalReviews'] ?? 0),
            'avgRating' => round((float) ($result['avgRating'] ?? 0), 2),
        ];
    }
}
