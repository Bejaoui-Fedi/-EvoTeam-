<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$appointmentRepo = $container->get('doctrine')->getRepository(\App\Entity\Appointment::class);

$appointments = $appointmentRepo->findAll();

echo "--- Appointments ---\n";
foreach ($appointments as $app) {
    echo "ID: " . $app->getId() . " | Date: " . $app->getDateRdv()->format('Y-m-d') . " | UserId: " . $app->getUserId() . " | ProId: " . $app->getProfessionalId() . " | ProName: " . $app->getProfessionalName() . "\n";
}
echo "--------------------\n";
