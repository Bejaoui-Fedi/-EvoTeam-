<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Appointment;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$userRepository = $container->get('doctrine')->getRepository(\App\Entity\User::class);

// Create a Dummy Appointment
$appointment = new Appointment();
$appointment->setProfessionalId(68); // ID we just created
$appointment->setDateRdv(new \DateTime('+1 day'));
$appointment->setHeureRdv(new \DateTime('10:00'));
$appointment->setMotif('Test Consultation');
$appointment->setTypeRdv('En ligne');
$appointment->setUserId(67); // Admin ID

// Simulate the sync logic from controller (since it's private, we just test the logic here)
$proId = $appointment->getProfessionalId();
if ($proId) {
    $proUser = $userRepository->find($proId);
    if ($proUser) {
        $appointment->setProfessionalName($proUser->getNom());
    }
}

echo "Mapped Professional Name: " . $appointment->getProfessionalName() . "\n";
if ($appointment->getProfessionalName() === "Dr. Green") {
    echo "SUCCESS: Sync Logic Validated.\n";
} else {
    echo "FAILURE: Sync Logic Failed. Found: " . ($appointment->getProfessionalName() ?: 'None') . "\n";
}
