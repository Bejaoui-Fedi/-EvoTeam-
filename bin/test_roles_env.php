<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$userRepo = $container->get('doctrine')->getRepository(\App\Entity\User::class);

$admin = $userRepo->findOneBy(['email' => 'admin@gmail.com']); // Assumed admin email based on previous query
if (!$admin) {
    // Try to find ANY admin
    $admin = $userRepo->createQueryBuilder('u')
        ->where("u.email LIKE '%admin%'")
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}

if ($admin) {
    echo "Found Admin: " . $admin->getEmail() . "\n";
    echo "Raw Role Property: " . $admin->getRole() . "\n";
    echo "Roles Array: " . implode(', ', $admin->getRoles()) . "\n";
} else {
    echo "No admin found in DB.\n";
}
