<?php

namespace App\Controller;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppointmentConfirmController extends AbstractController
{
    #[Route('/rdv/confirm/{id}/{token}', name: 'app_appointment_smart_confirm', methods: ['GET'])]
    public function confirm(Appointment $appointment, string $token, EntityManagerInterface $entityManager): Response
    {
        $secretKey = $_ENV['APP_SECRET'] ?? 'EVOLIACMNA2026';
        $rdvDate = $appointment->getDateRdv();
        
        $expectedToken = md5($appointment->getId() . $secretKey . ($rdvDate ? $rdvDate->format('Ymd') : ''));

        if ($token !== $expectedToken) {
            $this->addFlash('danger', 'Lien de confirmation invalide ou expiré.');
            return $this->redirectToRoute('app_user_login');
        }

        if ($appointment->getStatut() === 'Annulé') {
            $this->addFlash('warning', 'Ce rendez-vous a déjà été annulé.');
            return $this->redirectToRoute('app_patient_workspace');
        }

        if ($appointment->getStatut() === 'Confirmé') {
            $this->addFlash('info', 'Votre rendez-vous est déjà confirmé !');
        } else {
            $appointment->setStatut('Confirmé');
            $entityManager->flush();
            $this->addFlash('success', 'Merci ! Votre présence au rendez-vous est bien confirmée.');
        }

        return $this->redirectToRoute('app_patient_workspace');
    }
}
