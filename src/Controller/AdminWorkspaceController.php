<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/workspace')]
class AdminWorkspaceController extends AbstractController
{
    #[Route('/', name: 'app_admin_workspace', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointmentRepository, ConsultationRepository $consultationRepository): Response
    {
        $view = $request->query->get('view', 'rdv');
        
        $appointments = [];
        $consultations = [];

        if ($view === 'rdv') {
            $appointments = $appointmentRepository->findBy([], ['dateRdv' => 'DESC']);
        } else {
            $consultations = $consultationRepository->findBy([], ['dateConsultation' => 'DESC']);
        }

        return $this->render('admin_workspace/index.html.twig', [
            'view' => $view,
            'appointments' => $appointments,
            'consultations' => $consultations,
        ]);
    }

    #[Route('/appointment/{id}/delete', name: 'app_admin_workspace_rdv_delete', methods: ['POST'])]
    public function deleteRdv(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $consultation = $entityManager->getRepository(\App\Entity\Consultation::class)->findOneBy(['appointment' => $appointment]);
            if ($consultation) {
                $entityManager->remove($consultation);
            }
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('danger', 'Rendez-vous #' . $appointment->getId() . ' supprimé.');
        }
        return $this->redirectToRoute('app_admin_workspace', ['view' => 'rdv']);
    }

    #[Route('/consultation/{id}/delete', name: 'app_admin_workspace_consultation_delete', methods: ['POST'])]
    public function deleteConsultation(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $consultation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($consultation);
            $entityManager->flush();
            $this->addFlash('danger', 'Consultation #' . $consultation->getId() . ' supprimée.');
        }
        return $this->redirectToRoute('app_admin_workspace', ['view' => 'consultations']);
    }
}
