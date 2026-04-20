<?php

use App\Kernel;
use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/Esprit-PIDEV-3A23-2526-Evolia-/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/Esprit-PIDEV-3A23-2526-Evolia-/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$userRepo = $em->getRepository(User::class);

$users = $userRepo->findAll();
$pros = [];
$patients = [];

foreach ($users as $u) {
    if ($u->getRole() === 'ROLE_PSYCHOLOGUE' || $u->getRole() === 'ROLE_COACH') {
        $pros[] = $u;
    } else {
        $patients[] = $u;
    }
}

if (empty($pros) || empty($patients)) {
    echo "Pas assez d'utilisateurs ou de psychologues/coachs dans la base de données. Créez-en quelques-uns d'abord.\n";
    exit(1);
}

$pro = $pros[0];
$proName = $pro->getNom();
$proId = $pro->getId();

echo "Psychologue sélectionné: " . $proName . " (ID: $proId)\n";

$statuses = ['En attente', 'Confirmé', 'Annulé'];
$types = ['Présentiel', 'En ligne'];

// Mots au hasard pour les motifs
$motifs = [
    "Besoin de discuter d'un burn-out récent.",
    "Stress intense au travail.",
    "Troubles du sommeil et anxiété.",
    "Baisse de moral et problèmes personnels.",
    "Suivi régulier suite à la séance de la semaine dernière.",
    "Coaching pour un objectif professionnel."
];

// Créer 8 rendez-vous
$count = 0;
foreach ($patients as $idx => $patient) {
    for ($i=0; $i<2; $i++) {
        $count++;
        $date = new \DateTime();
        $date->modify(sprintf('%s %d days', rand(0,1) ? '+' : '-', rand(1, 14)));
        $date->setTime(rand(9, 16), rand(0,1) ? 0 : 30);
        
        $appt = new Appointment();
        $appt->setDateRdv($date);
        $appt->setHeureRdv($date);
        $appt->setTypeRdv($types[array_rand($types)]);
        $appt->setStatut($statuses[array_rand($statuses)]);
        $appt->setMotif($motifs[array_rand($motifs)]);
        $appt->setUserId($patient->getId());
        $appt->setProfessionalId($proId);
        $appt->setProfessionalName($proName);
        
        $em->persist($appt);

        // Create consultation if confirmed and date is past
        if ($appt->getStatut() === 'Confirmé' && $date < new \DateTime()) {
            $appt->setStatut('Terminé'); // Let's pretend it's finished
            
            $cons = new Consultation();
            $cons->setAppointment($appt);
            $cons->setDateConsultation($date);
            $cons->setDiagnostic("Diagnostic pour le motif: " . substr($appt->getMotif(), 0, 15) . "...");
            $cons->setObservation("Patient calme, discussion productive. A revoir.");
            $cons->setTraitement("Exercices de respiration, marche de 30 min par jour.");
            $cons->setDuree(rand(30, 60));
            $cons->setStatutConsultation('Terminée');
            
            $em->persist($cons);
        }
    }
}

$em->flush();
echo "$count Rendez-vous et Consultations créés avec succès.\n";
