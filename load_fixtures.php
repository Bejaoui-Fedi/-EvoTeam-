<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$connection = $kernel->getContainer()->get('doctrine')->getConnection();

$sql = file_get_contents(__DIR__.'/fixtures.sql');
$connection->executeStatement($sql);

echo "✅ Données de démonstration insérées avec succès !";
