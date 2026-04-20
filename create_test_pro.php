<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Create a Professional
$pro = new User();
$pro->setNom("Dr. Green");
$pro->setEmail("green@evolia.com");
$pro->setPassword("password"); // Not hashed for simplicity in test if manual check
$pro->setRole("PSY_COACH");
$pro->setActif(true);

$entityManager->persist($pro);
$entityManager->flush();

echo "Test Professional Created with ID: " . $pro->getId() . "\n";
