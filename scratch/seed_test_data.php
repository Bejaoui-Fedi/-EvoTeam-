<?php

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// 1. MASSIVE CLEANUP
$entityManager->createQuery('DELETE FROM App\Entity\Consultation')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\Appointment')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\UserProfile')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

echo "[-] Base de donnees nettoyee.\n";

$password = 'jbeli2020';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$phone = '26562760';

// 2. USERS
$usersData = [
    ['email' => 'amina.patient@evolia.tn', 'nom' => 'Amina B.', 'role' => 'ROLE_PATIENT'],
    ['email' => 'karim.lapin@evolia.tn', 'nom' => 'Karim (Patient Bloqué)', 'role' => 'ROLE_PATIENT'],
    ['email' => 'youssef.patient@evolia.tn', 'nom' => 'Youssef T.', 'role' => 'ROLE_PATIENT'],
    ['email' => 'dr.fedi@evolia.tn', 'nom' => 'Dr. Fedi', 'role' => 'ROLE_PSYCHOLOGUE'],
    ['email' => 'hedia.coach@evolia.tn', 'nom' => 'Hedia', 'role' => 'ROLE_COACH'],
    ['email' => 'admin@evolia.tn', 'nom' => 'Super Admin', 'role' => 'ROLE_ADMIN'],
];

$users = [];
foreach ($usersData as $data) {
    $u = new User();
    $u->setEmail($data['email']);
    $u->setNom($data['nom']);
    $u->setRole($data['role']);
    $u->setTelephone($phone);
    $u->setActif(true);
    $u->setPassword($hashedPassword);
    $entityManager->persist($u);
    $users[$data['email']] = $u;
    
    $p = new UserProfile();
    $p->setUser($u);
    $p->setBio($data['role'] === 'ROLE_PATIENT' ? 'Patient Evolia.' : 'Specialiste diplome avec plus de 10 ans.');
    $p->setLangue('fr');
    $p->setNotificationsEmail(true);
    $p->setNotificationsSms(true);
    $p->setDateCreation(new \DateTime());
    $entityManager->persist($p);
}
$entityManager->flush();
echo "[+] ".count($users)." Utilisateurs crees.\n";

$psy = $users['dr.fedi@evolia.tn'];
$coach = $users['hedia.coach@evolia.tn'];
$patientNormal = $users['amina.patient@evolia.tn'];
$patientBan = $users['karim.lapin@evolia.tn'];
$patientY = $users['youssef.patient@evolia.tn'];

// 3. APPOINTMENTS
$appointments = [];

for ($i = 1; $i <= 3; $i++) {
    $a = new Appointment();
    $a->setUserId($patientBan->getId());
    $a->setProfessionalId($psy->getId());
    $a->setProfessionalName($psy->getNom());
    $a->setDateRdv(new \DateTime("-{$i} days"));
    $a->setHeureRdv(new \DateTime('10:00:00'));
    $a->setMotif("Rendez-vous oublie #{$i}.");
    $a->setTypeRdv("Présentiel");
    $a->setIsUrgent(false);
    $a->setStatut("Absent");
    $entityManager->persist($a);
    $appointments[] = $a;
}

$conge = new Appointment();
$conge->setUserId($psy->getId()); 
$conge->setProfessionalId($psy->getId());
$conge->setProfessionalName($psy->getNom());
$conge->setDateRdv(new \DateTime('tomorrow'));
$conge->setHeureRdv(new \DateTime('08:00:00'));
$conge->setMotif("Absence declaree.");
$conge->setTypeRdv("Présentiel");
$conge->setIsUrgent(false);
$conge->setStatut("Congé");
$entityManager->persist($conge);
$appointments[] = $conge;

$urgent = new Appointment();
$urgent->setUserId($patientNormal->getId());
$urgent->setProfessionalId($coach->getId());
$urgent->setProfessionalName($coach->getNom());
$urgent->setDateRdv(new \DateTime('+3 days'));
$urgent->setHeureRdv(new \DateTime('14:30:00'));
$urgent->setMotif("Je suis dans une profonde detresse, j ai des crises de panique ingerables et des pensées sombres. Aidez moi.");
$urgent->setTypeRdv("En ligne");
$urgent->setIsUrgent(true); 
$urgent->setStatut("Confirmé");
$entityManager->persist($urgent);
$appointments[] = $urgent;

$smart = new Appointment();
$smart->setUserId($patientY->getId());
$smart->setProfessionalId($psy->getId());
$smart->setProfessionalName($psy->getNom());
$smart->setDateRdv(new \DateTime('+2 days'));
$smart->setHeureRdv(new \DateTime('11:00:00'));
$smart->setMotif("Gestion de l'anxiete au travail.");
$smart->setTypeRdv("Présentiel");
$smart->setIsUrgent(false);
$smart->setStatut("En attente"); 
$entityManager->persist($smart);
$appointments[] = $smart;

$consultationsData = [
    ["Troubles du sommeil", "Le patient presente une legere insomnie", "Prescription en ligne."],
    ["Burn-out professionnel", "Syndrome acté.", "Arret de travail necessaire."],
    ["Suivi phobie sociale.", "Lente amelioration.", "Renforcement positif."],
    ["Anxiete performance.", "Peur panique echec.", "Techniques de respiration."],
    ["Coaching de leadership", "Manque d'assurance.", "Travail posture."],
];

foreach ($consultationsData as $idx => $cData) {
    $a = new Appointment();
    $a->setUserId($patientY->getId());
    $proTarget = ($idx < 4) ? $psy : $coach;
    $a->setProfessionalId($proTarget->getId());
    $a->setProfessionalName($proTarget->getNom());
    $a->setDateRdv(new \DateTime("-".($idx+3)." days"));
    $a->setHeureRdv(new \DateTime('15:00:00'));
    $a->setMotif($cData[0]);
    $a->setTypeRdv("Présentiel");
    $a->setIsUrgent(false);
    $a->setStatut("Terminé");
    $entityManager->persist($a);
    $appointments[] = $a;

    $c = new Consultation();
    $c->setAppointment($a);
    $c->setDateConsultation($a->getDateRdv());
    $c->setDiagnostic($cData[1]);
    $c->setTraitement($cData[2]);
    $c->setDuree(45);
    $c->setStatutConsultation("Validée");
    $entityManager->persist($c);
}

for ($k = 0; $k < 6; $k++) {
    $a = new Appointment();
    $a->setUserId($patientNormal->getId());
    $a->setProfessionalId($psy->getId());
    $a->setProfessionalName($psy->getNom());
    $a->setDateRdv(new \DateTime("+".($k+4)." days"));
    $a->setHeureRdv(new \DateTime('09:00:00'));
    $a->setMotif("Seance habituelle #".$k);
    $a->setTypeRdv("En ligne");
    $a->setIsUrgent(false);
    $a->setStatut("En attente");
    $entityManager->persist($a);
    $appointments[] = $a;
}

$entityManager->flush();
echo "[+] ".count($appointments)." Rendez-vous et 5 Consultations crees ! \n";
