<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

echo "=== USERS ===\n";
$users = $entityManager->getRepository(\App\Entity\User::class)->findAll();
foreach ($users as $u) {
    echo "ID " . $u->getId() . " : " . $u->getNom() . " (Role: " . $u->getRole() . ")\n";
}

echo "\n=== APPOINTMENTS ===\n";
$rdvs = $entityManager->getRepository(\App\Entity\Appointment::class)->findAll();
foreach ($rdvs as $r) {
    echo "ID " . $r->getId() . " : Patient " . $r->getUserId() . " con Pro " . $r->getProfessionalId() . " (Name: " . $r->getProfessionalName() . ") Statut: " . $r->getStatut() . "\n";
}
