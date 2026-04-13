<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$r = $em->getRepository('App\Entity\Appointment')->findAll();
$lines = [];
foreach($r as $a) {
    echo $a->getId() . " | ProId: " . $a->getProfessionalId() . " | ProName: " . $a->getProfessionalName() . "\n";
}
