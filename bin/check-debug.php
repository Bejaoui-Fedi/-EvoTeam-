<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();

// Find appointment 32
$app = $em->getRepository('App\Entity\Appointment')->find(32);
if ($app) {
    echo "Appointment 32:\n";
    echo "ProId: " . $app->getProfessionalId() . " (" . gettype($app->getProfessionalId()) . ")\n";
    echo "UserId: " . $app->getUserId() . " (" . gettype($app->getUserId()) . ")\n";
}

// Emulate exactly what the controller does:
$proId = 20;

$qb = $em->getRepository('App\Entity\Appointment')->createQueryBuilder('a');
$qb->andWhere('a.professionalId = :proId')->setParameter('proId', $proId);
$res = $qb->getQuery()->getResult();
echo "Found for ProId 20: " . count($res) . "\n";

$qb2 = $em->getRepository('App\Entity\Appointment')->createQueryBuilder('a');
$qb2->andWhere('a.professionalId = :proId')->setParameter('proId', '20');
$res2 = $qb2->getQuery()->getResult();
echo "Found for ProId '20': " . count($res2) . "\n";
