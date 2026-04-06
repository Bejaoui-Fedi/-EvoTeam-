<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$repository = $entityManager->getRepository(\App\Entity\Appointment::class);

$proId = 65; // User ID reported as "flen"
echo "Pro ID to search: " . $proId . "\n";

$queryBuilder = $repository->createQueryBuilder('a');
$queryBuilder->andWhere('a.professionalId = :proId')->setParameter('proId', $proId);
$appointments = $queryBuilder->orderBy('a.dateRdv', 'DESC')->getQuery()->getResult();

echo "Appointments found: " . count($appointments) . "\n";
foreach ($appointments as $a) {
    echo "ID: " . $a->getId() . " | Statut: " . $a->getStatut() . " | Pro ID: " . $a->getProfessionalId() . "\n";
}
