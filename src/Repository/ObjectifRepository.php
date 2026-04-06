<?php

namespace App\Repository;

use App\Entity\Objectif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objectif>
 */
class ObjectifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objectif::class);
    }

    /**
     * Recherche avancée avec multicritères (Recherche, Statut, Tri)
     */
    public function findByCriteria(?string $search = null, ?string $statut = null, string $sort = 'dateDebut', string $direction = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($search) {
            $qb->andWhere('o.titre LIKE :search OR o.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('o.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Sécurité sur le tri
        $allowedSorts = ['dateDebut', 'dateFin', 'titre', 'statut'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateDebut';
        }

        $qb->orderBy('o.' . $sort, $direction === 'ASC' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }
}
