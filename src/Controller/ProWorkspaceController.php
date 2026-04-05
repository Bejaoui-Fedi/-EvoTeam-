<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Form\ConsultationType;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pro/workspace')]
class ProWorkspaceController extends AbstractController
{
    #[Route('/', name: 'app_pro_workspace', methods: ['GET', 'POST'])]
    public function index(Request $request, AppointmentRepository $appointmentRepository, ConsultationRepository $consultationRepository, EntityManagerInterface $entityManager): Response
    {
        $view = $request->query->get('view', 'rdv');
        $selectedRdvId = $request->query->get('appointment_id');
        
        $appointments = [];
        $consultations = [];
        $formView = null;
        $activeAppointment = null;

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_user_login');
        }
        $proId = $user->getId();

        if ($view === 'rdv') {
            $date = $request->query->get('date');
            $statut = $request->query->get('statut');
            
            $queryBuilder = $appointmentRepository->createQueryBuilder('a');
            $queryBuilder->andWhere('a.professionalId = :proId')->setParameter('proId', $proId);

            if ($date) {
                $queryBuilder->andWhere('a.dateRdv = :date')->setParameter('date', $date);
            }
            if ($statut && $statut !== 'Tous') {
                $queryBuilder->andWhere('a.statut = :statut')->setParameter('statut', $statut);
            }
            $appointments = $queryBuilder->orderBy('a.dateRdv', 'DESC')->getQuery()->getResult();
        } else {
            // VIEW = CONSULTATIONS
            $consultations = $consultationRepository->createQueryBuilder('c')
                ->join('c.appointment', 'a')
                ->where('a.professionalId = :proId')
                ->setParameter('proId', $proId)
                ->orderBy('c.dateConsultation', 'DESC')
                ->getQuery()
                ->getResult();
            
            $consultation = new Consultation();
            if ($selectedRdvId) {
                $activeAppointment = $appointmentRepository->find($selectedRdvId);
                if ($activeAppointment) {
                    $existingConsultation = $consultationRepository->findOneBy(['appointment' => $activeAppointment]);
                    if ($existingConsultation) {
                        $consultation = $existingConsultation; // Load existing for editing
                    } else {
                        $consultation->setAppointment($activeAppointment);
                        $consultation->setDateConsultation(new \DateTime());
                    }
                }
            }

            $form = $this->createForm(ConsultationType::class, $consultation);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($consultation);
                $entityManager->flush();
                $this->addFlash('success', 'Consultation #'. $consultation->getId() .' enregistrée.');
                return $this->redirectToRoute('app_pro_workspace', ['view' => 'consultations']);
            }
            $formView = $form->createView();
        }

        return $this->render('pro_workspace/index.html.twig', [
            'view' => $view,
            'appointments' => $appointments,
            'consultations' => $consultations,
            'form' => $formView,
            'activeAppointment' => $activeAppointment,
            'current_date' => $request->query->get('date'),
            'current_statut' => $request->query->get('statut'),
        ]);
    }

    #[Route('/appointment/{id}/update-status', name: 'app_pro_workspace_rdv_update_status', methods: ['POST'])]
    public function updateRdvStatus(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('statut');
        if ($newStatus) {
            $appointment->setStatut($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Statut mis à jour pour RDV #' . $appointment->getId());
        }
        return $this->redirectToRoute('app_pro_workspace', ['view' => 'rdv']);
    }

    #[Route('/appointment/{id}/delete', name: 'app_pro_workspace_rdv_delete', methods: ['POST'])]
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
        return $this->redirectToRoute('app_pro_workspace', ['view' => 'rdv']);
    }

    #[Route('/consultation/{id}/delete', name: 'app_pro_workspace_consultation_delete', methods: ['POST'])]
    public function deleteConsultation(Request $request, Consultation $consultation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $consultation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($consultation);
            $entityManager->flush();
            $this->addFlash('danger', 'Consultation #' . $consultation->getId() . ' supprimée.');
        }
        return $this->redirectToRoute('app_pro_workspace', ['view' => 'consultations']);
    }
}
