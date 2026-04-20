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
    name: 'app:send-sms-reminders',
    description: 'Envoie des rappels SMS aux patients 24h avant leur rendez-vous.',
)]
class SendSmsReminderCommand extends Command
{
    private AppointmentRepository $appointmentRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppointmentRepository $appointmentRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->appointmentRepository = $appointmentRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Elite Communication : Envoi des rappels SMS');

        // On cherche les RDV pour demain
        $tomorrow = new \DateTime('tomorrow');
        $appointments = $this->appointmentRepository->findBy([
            'dateRdv' => $tomorrow,
            'isSmsSent' => false,
            'statut' => 'En attente'
        ]);

        if (empty($appointments)) {
            $io->info('Aucun rappel à envoyer pour demain.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($appointments as $appointment) {
            $patient = $this->userRepository->find($appointment->getUserId());
            
            if ($patient && $patient->getTelephone()) {
                try {
                    $this->notificationService->sendSmsReminder($appointment, $patient);
                    $appointment->setIsSmsSent(true);
                    $count++;
                    $io->text("✅ SMS envoyé à : " . $patient->getNom() . " (id #{$appointment->getId()})");
                } catch (\Exception $e) {
                    $io->error("❌ Échec pour #{$appointment->getId()} : " . $e->getMessage());
                }
            }
        }

        $this->entityManager->flush();
        $io->success("$count rappel(s) SMS envoyé(s) avec succès !");

        return Command::SUCCESS;
    }
}
