<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:appointments:auto-close',
    description: 'Clôture automatiquement les anciens rendez-vous non finalisés (passage à Terminé).',
)]
class AutoCloseConsultationsCommand extends Command
{
    private AppointmentRepository $appointmentRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(AppointmentRepository $appointmentRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->appointmentRepository = $appointmentRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Clôture Automatique des Rendez-vous (Clean-Up)');

        $now = new \DateTime();
        // On récupère tous les statuts potentiellement restés ouverts
        $appointments = $this->appointmentRepository->createQueryBuilder('a')
            ->where("a.statut IN ('Confirmé', 'Accepté')")
            ->getQuery()->getResult();

        $closedCount = 0;

        foreach ($appointments as $appointment) {
            $rdvDate = $appointment->getDateRdv();
            $rdvTime = $appointment->getHeureRdv();
            
            if ($rdvDate && $rdvTime) {
                $rdvDateTime = new \DateTime($rdvDate->format('Y-m-d') . ' ' . $rdvTime->format('H:i:s'));
                
                // Si le RDV a eu lieu il y a plus de 48H
                $deadline = clone $now;
                $deadline->modify('-48 hours');

                if ($rdvDateTime < $deadline) {
                    $appointment->setStatut('Terminé');
                    $closedCount++;
                    
                    $io->text('RDV #' . $appointment->getId() . ' du ' . $rdvDateTime->format('d/m/Y') . ' clôturé (Terminé).');
                }
            }
        }

        if ($closedCount > 0) {
            $this->entityManager->flush();
            $io->success($closedCount . ' rendez-vous anciens ont été automatiquement clôturés.');
        } else {
            $io->success('Aucun rendez-vous ancien de plus de 48h n\'a été trouvé.');
        }

        return Command::SUCCESS;
    }
}
