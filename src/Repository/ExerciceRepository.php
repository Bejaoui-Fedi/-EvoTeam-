<?php

namespace App\Repository;

use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercice>
 */
class ExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    /**
     * Recherche avancée avec multicritères (Recherche, Difficulté, Tri)
     */
    public function findByCriteria(?string $search = null, ?string $difficulte = null, string $sort = 'date', string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search OR e.type LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
               ->setParameter('difficulte', $difficulte);
        }

        // Sécurité sur le tri
        $allowedSorts = ['date', 'titre', 'duree', 'difficulte', 'type'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date';
        }

        $qb->orderBy('e.' . $sort, $direction === 'ASC' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }
}
