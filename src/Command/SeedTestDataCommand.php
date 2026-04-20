<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Appointment;
use App\Entity\Consultation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-test-data',
    description: 'Peuplement massif de la base de données avec des données de test Evolia.',
)]
class SeedTestDataCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Génération massive de données de test Elite communication');

        // 1. CLEAN
        $this->entityManager->createQuery('DELETE FROM App\Entity\Consultation')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Appointment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u WHERE u.email LIKE :test OR u.email = :realEmail')
            ->setParameter('test', '%@evolia.tn')
            ->setParameter('realEmail', 'molka.jbeli25@gmail.com')
            ->execute();

        $password = 'jbeli2020';
        $phone = '+21626562760';

        // 2. USERS (Pros)
        $pro = new User();
        $pro->setEmail('pro@evolia.tn');
        $pro->setNom('Dr. Fedi (Elite Pro)');
        $pro->setRole('ROLE_PRO');
        $pro->setTelephone($phone);
        $pro->setActif(true);
        $pro->setPassword($this->passwordHasher->hashPassword($pro, $password));
        $this->entityManager->persist($pro);

        $admin = new User();
        $admin->setEmail('admin@evolia.tn');
        $admin->setNom('Admin Evolia');
        $admin->setRole('ROLE_ADMIN');
        $admin->setTelephone($phone);
        $admin->setActif(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));
        $this->entityManager->persist($admin);

        // 3. PATIENTS (Multiple)
        $patientsData = [
            ['email' => 'molka.jbeli25@gmail.com', 'nom' => 'Mme. Molka (Test Manager)'],
            ['email' => 'ahmed.patient@evolia.tn', 'nom' => 'Ahmed Jebali'],
            ['email' => 'nour.patient@evolia.tn', 'nom' => 'Nour Jebali'],
            ['email' => 'hedia.patient@evolia.tn', 'nom' => 'Hedia B.'],
        ];

        $patients = [];
        foreach ($patientsData as $data) {
            $u = new User();
            $u->setEmail($data['email']);
            $u->setNom($data['nom']);
            $u->setRole('ROLE_PATIENT');
            $u->setTelephone($phone);
            $u->setActif(true);
            $u->setPassword($this->passwordHasher->hashPassword($u, $password));
            $this->entityManager->persist($u);
            $patients[] = $u;
        }
        $this->entityManager->flush();

        // 4. APPOINTMENTS (Varied)
        $motifs = [
            'Normal' => "Besoin d'un suivi pour ma gestion du temps et du stress.",
            'Urgent_Psy' => "C'est une crise de panique, j'ai des pensées de suicide.",
            'Urgent_Med' => "J'ai une douleur intense à la poitrine et je saigne.",
            'Past' => "Séance d'introduction terminée."
        ];

        // A. Appointments for the main user (Molka)
        $this->createRdv($patients[0], $pro, new \DateTime('today 14:00'), $motifs['Urgent_Psy'], 'Visioconférence', true, 'En attente');
        $this->createRdv($patients[0], $pro, new \DateTime('tomorrow 09:00'), $motifs['Normal'], 'Présentiel', false, 'En attente');
        
        // B. Appointments for other patients
        $this->createRdv($patients[1], $pro, new \DateTime('today 10:00'), $motifs['Normal'], 'Présentiel', false, 'Confirmé');
        $this->createRdv($patients[2], $pro, new \DateTime('today 11:30'), $motifs['Urgent_Med'], 'Urgence', true, 'En attente');
        $this->createRdv($patients[3], $pro, new \DateTime('-2 days 15:00'), $motifs['Past'], 'Visioconférence', false, 'Terminé');

        $this->entityManager->flush();

        // 5. CONSULTATION FOR THE PAST ONE
        $pastRdv = $this->entityManager->getRepository(Appointment::class)->findOneBy(['statut' => 'Terminé']);
        if ($pastRdv) {
            $c = new Consultation();
            $c->setAppointment($pastRdv);
            $c->setDateConsultation($pastRdv->getDateRdv());
            $c->setDiagnostic("Patient stable. Diminution des symptômes d'anxiété.");
            $c->setTraitement("Continuer les exercices. RDV de contrôle dans 15 jours.");
            $c->setDuree(30);
            $c->setStatutConsultation("Validée");
            $this->entityManager->persist($c);
        }

        $this->entityManager->flush();

        $io->success('Base de données re-peuplée !');
        $io->listing([
            'Principal Patient : molka.jbeli25@gmail.com',
            'Autres patients (Ahmed, Nour...) créés.',
            'Rendez-vous Urgents (Alerte SMS) et Normaux générés.',
            'Mots de passe : jbeli2020'
        ]);

        return Command::SUCCESS;
    }

    private function createRdv(User $p, User $pro, \DateTime $date, string $motif, string $type, bool $urgent, string $statut)
    {
        $r = new Appointment();
        $r->setUserId($p->getId());
        $r->setProfessionalId($pro->getId());
        $r->setProfessionalName($pro->getNom());
        $r->setDateRdv($date);
        $r->setHeureRdv($date);
        $r->setMotif($motif);
        $r->setTypeRdv($type);
        $r->setIsUrgent($urgent);
        $r->setStatut($statut);
        $this->entityManager->persist($r);
    }
}
