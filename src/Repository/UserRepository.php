<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
<<<<<<< HEAD
=======
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
<<<<<<< HEAD
=======

    /**
     * @return User[] Returns an array of User objects
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.nom LIKE :val OR u.email LIKE :val OR u.telephone LIKE :val OR u.role LIKE :val')
            ->setParameter('val', '%' . $query . '%')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return User[] Returns an array of User objects filtered by query and role
     */
    public function searchWithRole(string $query, string $role): array
    {
        $qb = $this->createQueryBuilder('u');
        
        if (!empty($query)) {
            $qb->andWhere('u.nom LIKE :val OR u.email LIKE :val OR u.telephone LIKE :val')
               ->setParameter('val', '%' . $query . '%');
        }
        
        if (!empty($role)) {
            if ($role === 'PSY_COACH') {
                $qb->andWhere('u.role IN (:roles)')
                   ->setParameter('roles', ['ROLE_PSYCHOLOGUE', 'ROLE_COACH']);
            } else {
                $qb->andWhere('u.role = :role')
                   ->setParameter('role', $role);
            }
        }
        
        return $qb->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
>>>>>>> eabe32bd1d39cea1a5a722f0ae36928346b48e11
}
