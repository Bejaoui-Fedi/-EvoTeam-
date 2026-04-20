<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:appointments:send-confirmations',
    description: 'Envoie un email Smart Confirm J-2 pour les rendez-vous.',
)]
class SendConfirmationsCommand extends Command
{
    private AppointmentRepository $appointmentRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;

    public function __construct(AppointmentRepository $appointmentRepository, UserRepository $userRepository, NotificationService $notificationService)
    {
        parent::__construct();
        $this->appointmentRepository = $appointmentRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Envoi des demandes de confirmation (Smart Confirm - J-2)');

        // We target appointments that are exactly inside the +48h window and are "En attente"
        $now = new \DateTime();
        $targetDate = clone $now;
        $targetDate->modify('+2 days');
        
        $targetDateString = $targetDate->format('Y-m-d');

        // Note: in a real big app we would check if we already sent the email. 
        // With zero-migration, we can either re-send it each run, or just find "En attente" since once confirmed it changes to "Confirmé".
        // Perfect! If they haven't confirmed, they stay "En attente".
        
        $pendingAppointments = $this->appointmentRepository->findBy([
            'statut' => 'En attente'
        ]);

        $sentCount = 0;

        foreach ($pendingAppointments as $appointment) {
            $rdvDate = $appointment->getDateRdv();
            if ($rdvDate && $rdvDate->format('Y-m-d') === $targetDateString) {
                // Generate a secure, deterministic zero-migration token
                $secretKey = $_ENV['APP_SECRET'] ?? 'EVOLIACMNA2026';
                $token = md5($appointment->getId() . $secretKey . $rdvDate->format('Ymd'));

                $patient = $this->userRepository->find($appointment->getUserId());
                if ($patient) {
                    $this->notificationService->sendSmartConfirmEmail($appointment, $patient, $token);
                    $sentCount++;
                    $io->text('Email envoyé pour le RDV #' . $appointment->getId() . ' (Patient: ' . $patient->getNom() . ').');
                }
            }
        }

        if ($sentCount > 0) {
            $io->success($sentCount . ' e-mails Smart Confirm ont été envoyés.');
        } else {
            $io->success('Aucun rendez-vous prévu pour J-2 nécessitant confirmation.');
        }

        return Command::SUCCESS;
    }
}
