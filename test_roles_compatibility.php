<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->load(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$userRepository = $container->get('doctrine.orm.entity_manager')->getRepository(\App\Entity\User::class);

$pros = $userRepository->findBy(['role' => 'PSY_COACH']);

echo "🔍 Vérification des utilisateurs avec le rôle PSY_COACH...\n";
if (empty($pros)) {
    echo "❌ AUCUN utilisateur trouvé avec le rôle PSY_COACH.\n";
    echo "⚠️ Le menu déroulant du formulaire sera vide.\n";
} else {
    echo "✅ " . count($pros) . " utilisateurs trouvés :\n";
    foreach ($pros as $pro) {
        echo " - ID: " . $pro->getId() . " | Nom: " . $pro->getNom() . " | Email: " . $pro->getEmail() . "\n";
    }
}
