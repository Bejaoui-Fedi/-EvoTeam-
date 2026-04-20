<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Checks if there's an overlapping appointment for a given date and time.
     * For now, it checks if the exact same slot is taken.
     */
    public function findOverlapping(Appointment $appointment): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.dateRdv = :date')
            ->andWhere('a.heureRdv = :heure')
            ->setParameter('date', $appointment->getDateRdv())
            ->setParameter('heure', $appointment->getHeureRdv());

        if ($appointment->getId()) {
            $qb->andWhere('a.id != :id')
               ->setParameter('id', $appointment->getId());
        }

        return $qb->getQuery()->getResult();
    }
}
