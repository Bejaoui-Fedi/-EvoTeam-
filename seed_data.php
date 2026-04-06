<?php

use App\Entity\Objectif;
use App\Entity\Exercice;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/.env.local')) {
    (new Dotenv())->bootEnv(__DIR__.'/.env.local');
} else {
    (new Dotenv())->bootEnv(__DIR__.'/.env');
}

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$data = [
    [
        'titre' => 'Endurance Cardio Automnale',
        'desc' => 'Améliorer le souffle et la résistance cardiaque.',
        'statut' => 'moyen',
        'exercices' => [
            ['titre' => 'Course à pied', 'desc' => 'Course légère en forêt.', 'type' => 'Endurance', 'diff' => 'moyen', 'duree' => 30],
            ['titre' => 'Corde à sauter', 'desc' => 'Séries intensives.', 'type' => 'Cardio', 'diff' => 'difficile', 'duree' => 15],
        ]
    ],
    [
        'titre' => 'Force et Puissance',
        'desc' => 'Développer la masse musculaire et la force explosive.',
        'statut' => 'avance',
        'exercices' => [
            ['titre' => 'Pompes classiques', 'desc' => 'Séries de 20 répétitions.', 'type' => 'Musculation', 'diff' => 'moyen', 'duree' => 20],
            ['titre' => 'Squats lestés', 'desc' => 'Travail des jambes.', 'type' => 'Musculation', 'diff' => 'difficile', 'duree' => 25],
        ]
    ],
    [
        'titre' => 'Zen et Flexibilité',
        'desc' => 'Retrouver de la mobilité articulaire et du calme.',
        'statut' => 'debutant',
        'exercices' => [
            ['titre' => 'Salutation au Soleil', 'desc' => 'Routine de Yoga matinale.', 'type' => 'Yoga', 'diff' => 'debutant', 'duree' => 20],
            ['titre' => 'Étirements complets', 'desc' => 'Focus sur le dos et les hanches.', 'type' => 'Souplesse', 'diff' => 'moyen', 'duree' => 15],
        ]
    ]
];

foreach ($data as $objData) {
    $objectif = new Objectif();
    $objectif->setTitre($objData['titre']);
    $objectif->setDescription($objData['desc']);
    $objectif->setStatut($objData['statut']);
    $objectif->setDateDebut(new \DateTime());
    $objectif->setDateFin((new \DateTime())->modify('+30 days'));
    
    $em->persist($objectif);
    
    foreach ($objData['exercices'] as $exData) {
        $ex = new Exercice();
        $ex->setTitre($exData['titre']);
        $ex->setDescription($exData['desc']);
        $ex->setType($exData['type']);
        $ex->setDifficulte($exData['diff']);
        $ex->setDuree($exData['duree']);
        $ex->setDate(new \DateTime());
        $ex->setObjectif($objectif);
        $em->persist($ex);
    }
}

$em->flush();
echo "Succès : 3 Objectifs et 6 Exercices ajoutés.";
