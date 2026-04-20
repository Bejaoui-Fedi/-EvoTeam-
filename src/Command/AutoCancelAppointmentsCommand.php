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
    name: 'app:appointments:auto-cancel',
    description: 'Annule automatiquement les rendez-vous "En attente" dont la date est dépassée.',
)]
class AutoCancelAppointmentsCommand extends Command
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
        $io->title('Évaluation des rendez-vous expirés');

        // Récupérer tous les RDV en attente
        $pendingAppointments = $this->appointmentRepository->findBy(['statut' => 'En attente']);
        $now = new \DateTime();

        $canceledCount = 0;

        foreach ($pendingAppointments as $appointment) {
            // Combiner dateRdv et heureRdv pour avoir le moment exact du RDV
            $rdvDate = $appointment->getDateRdv();
            $rdvTime = $appointment->getHeureRdv();
            
            if ($rdvDate && $rdvTime) {
                // Créer un DateTime représentant la date et l'heure prévues du RDV
                $rdvDateTime = new \DateTime($rdvDate->format('Y-m-d') . ' ' . $rdvTime->format('H:i:s'));
                
                // Si la date/heure du RDV est dépassée par rapport à l'heure actuelle
                if ($rdvDateTime < $now) {
                    $appointment->setStatut('Annulé');
                    $canceledCount++;
                    
                    $io->text('RDV #' . $appointment->getId() . ' du ' . $rdvDateTime->format('d/m/Y H:i') . ' annulé.');
                }
            }
        }

        if ($canceledCount > 0) {
            $this->entityManager->flush();
            $io->success($canceledCount . ' rendez-vous obsolètes ont été annulés automatiquement.');
        } else {
            $io->success('Aucun rendez-vous obsolète trouvé.');
        }

        return Command::SUCCESS;
    }
}
