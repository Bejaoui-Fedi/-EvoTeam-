<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

echo "=== ALL USERS ===\n";
$users = $entityManager->getRepository(\App\Entity\User::class)->findAll();
foreach ($users as $u) {
    echo "User ID " . $u->getId() . " | Name: " . $u->getNom() . " | Role: " . $u->getRole() . "\n";
}

echo "\n=== ALL APPOINTMENTS ===\n";
$rdvs = $entityManager->getRepository(\App\Entity\Appointment::class)->findAll();
foreach ($rdvs as $r) {
    echo "RDV ID " . $r->getId() . " | Patient " . $r->getUserId() . " | Pro (Saved ID) " . $r->getProfessionalId() . " | Pro Name " . $r->getProfessionalName() . " | Statut " . $r->getStatut() . "\n";
}
