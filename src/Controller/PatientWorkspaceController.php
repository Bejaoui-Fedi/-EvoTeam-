<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/patient/workspace')]
class PatientWorkspaceController extends AbstractController
{
    #[Route('/', name: 'app_patient_workspace', methods: ['GET'])]
    public function index(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_user_login');
        }
        $userId = $user->getId();
        $appointments = $appointmentRepository->findBy(['userId' => $userId], ['dateRdv' => 'DESC']);
        
        return $this->render('patient_workspace/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/appointment/new', name: 'app_patient_workspace_rdv_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Repository\UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_user_login');
        }

        $appointment = new Appointment();
        $appointment->setUserId($user->getId());
        $appointment->setStatut('En attente');

        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncProfessionalName($appointment, $userRepository);
            $entityManager->persist($appointment);
            $entityManager->flush();
            $this->addFlash('success', 'Votre rendez-vous a été pris avec succès.');
            return $this->redirectToRoute('app_patient_workspace');
        }

        return $this->render('patient_workspace/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/appointment/{id}/edit', name: 'app_patient_workspace_rdv_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $entityManager, \App\Repository\UserRepository $userRepository): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncProfessionalName($appointment, $userRepository);
            $entityManager->flush();
            $this->addFlash('info', 'Votre rendez-vous a été mis à jour avec succès.');
            return $this->redirectToRoute('app_patient_workspace');
        }

        return $this->render('patient_workspace/edit.html.twig', [
            'appointment' => $appointment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/appointment/{id}/cancel', name: 'app_patient_workspace_rdv_cancel', methods: ['POST'])]
    public function cancel(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $appointment->getId(), $request->request->get('_token'))) {
            $appointment->setStatut('Annulé');
            $entityManager->flush();
            $this->addFlash('warning', 'Rendez-vous annulé.');
        }
        return $this->redirectToRoute('app_patient_workspace');
    }

    #[Route('/appointment/{id}/delete', name: 'app_patient_workspace_rdv_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $consultation = $entityManager->getRepository(\App\Entity\Consultation::class)->findOneBy(['appointment' => $appointment]);
            if ($consultation) {
                // If a medical consultation exists, the patient cannot delete the appointment record.
                $this->addFlash('warning', 'Ce rendez-vous possède un dossier médical (consultation) et ne peut pas être supprimé.');
                return $this->redirectToRoute('app_patient_workspace');
            }
            $entityManager->remove($appointment);
            $entityManager->flush();
            $this->addFlash('danger', 'Rendez-vous supprimé définitivement.');
        }
        return $this->redirectToRoute('app_patient_workspace');
    }
    private function syncProfessionalName(Appointment $appointment, \App\Repository\UserRepository $userRepository): void
    {
        $proId = $appointment->getProfessionalId();
        if ($proId) {
            $proUser = $userRepository->find($proId);
            if ($proUser) {
                // We store the full name for direct display in workspaces (reduces JOINs/lookups)
                $fullName = $proUser->getNom();
                $appointment->setProfessionalName($fullName);
            } else {
                // Safety fallback if the professional is no longer found
                $appointment->setProfessionalName('Spécialiste (Indisponible)');
            }
        }
    }
}
