<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$userRepo = $container->get('doctrine')->getRepository(\App\Entity\User::class);

$pros = $userRepo->createQueryBuilder('u')
    ->where("u.role = 'PSY_COACH'")
    ->getQuery()
    ->getResult();

echo "--- Pros in DB ---\n";
foreach ($pros as $pro) {
    echo "ID: " . $pro->getId() . " | Email: " . $pro->getEmail() . " | Name: " . $pro->getNom() . "\n";
}
echo "------------------\n";
