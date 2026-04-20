<?php

namespace App\Command;

use App\Service\NotificationService;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-comm',
    description: 'Commande de diagnostic pour tester les emails et les SMS.',
)]
class TestCommCommand extends Command
{
    private NotificationService $notificationService;
    private UserRepository $userRepository;
    private AppointmentRepository $appointmentRepository;

    public function __construct(NotificationService $notificationService, UserRepository $userRepository, AppointmentRepository $appointmentRepository)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->userRepository = $userRepository;
        $this->appointmentRepository = $appointmentRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Elite Communication : Diagnostic');

        $users = $this->userRepository->findBy(['email' => 'molka.jbeli25@gmail.com']);
        $user = !empty($users) ? $users[0] : null;
        $appointment = $user ? $this->appointmentRepository->findOneBy(['userId' => $user->getId()]) : null;

        if (!$user || !$appointment) {
            $io->error('Utilisateur ou rendez-vous de test introuvable. Lancez app:seed-test-data d\'abord.');
            return Command::FAILURE;
        }

        // 1. TEST EMAIL
        $io->section('Test Email');
        try {
            $this->notificationService->sendConfirmationEmail($appointment, $user);
            $io->success('✅ Signal d\'envoi Email envoyé au serveur SMTP.');
        } catch (\Exception $e) {
            $io->error('❌ Échec Email : ' . $e->getMessage());
        }

        // 2. TEST SMS
        $io->section('Test SMS');
        try {
            $this->notificationService->sendSmsReminder($appointment, $user);
            $io->success('✅ Signal d\'envoi SMS envoyé à Twilio.');
        } catch (\Exception $e) {
            $io->error('❌ Échec SMS (Twilio) : ' . $e->getMessage());
            $io->note('Vérifiez que votre SID/Token dans le .env sont à jour.');
        }

        return Command::SUCCESS;
    }
}
