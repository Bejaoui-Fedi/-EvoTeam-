<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WellbeingTracker;
use App\Form\WellbeingTrackerType;
use App\Repository\WellbeingTrackerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wellbeing')]
class WellbeingTrackerController extends AbstractController
{
    #[Route('/', name: 'app_wellbeing_tracker_index', methods: ['GET'])]
    public function index(Request $request, WellbeingTrackerRepository $wellbeingTrackerRepository): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        $searchTerm = $request->query->get('search', '');

        // Build query based on user role
        $qb = $wellbeingTrackerRepository->createQueryBuilder('w')
            ->leftJoin('w.user', 'u')
            ->leftJoin('w.routineTask', 'r');

        // If not professional, filter by current user
        if (!$isProfessional) {
            $qb->andWhere('w.user = :user')
                ->setParameter('user', $user);
        }

        if ($searchTerm) {
            // Search by user nom or exercise title
            $qb->andWhere('u.nom LIKE :search OR r.title LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $wellbeingTrackers = $qb->orderBy('w.date', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('wellbeing_tracker/index.html.twig', [
            'wellbeing_trackers' => $wellbeingTrackers,
            'searchTerm' => $searchTerm,
            'isProfessional' => $isProfessional,
        ]);
    }

    // Add this route for patients (same as index but with different name for backward compatibility)
    #[Route('/patient', name: 'app_wellbeing_tracker_patient', methods: ['GET'])]
    public function patientIndex(Request $request, WellbeingTrackerRepository $wellbeingTrackerRepository): Response
    {
        // Redirect to the main index
        return $this->redirectToRoute('app_wellbeing_tracker_index', $request->query->all());
    }

    #[Route('/new', name: 'app_wellbeing_tracker_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $wellbeingTracker = new WellbeingTracker();
        $wellbeingTracker->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $wellbeingTracker->setUser($user);

        $form = $this->createForm(WellbeingTrackerType::class, $wellbeingTracker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($wellbeingTracker);
            $entityManager->flush();

            $this->addFlash('success', 'Entrée créée avec succès !');
            return $this->redirectToRoute('app_wellbeing_tracker_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('wellbeing_tracker/new.html.twig', [
            'wellbeing_tracker' => $wellbeingTracker,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wellbeing_tracker_show', methods: ['GET'])]
    public function show(WellbeingTracker $wellbeingTracker): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to view this entry
        if (!$isProfessional && $wellbeingTracker->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas voir cette entrée.');
        }

        return $this->render('wellbeing_tracker/show.html.twig', [
            'wellbeing_tracker' => $wellbeingTracker,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_wellbeing_tracker_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WellbeingTracker $wellbeingTracker, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to edit this entry
        if (!$isProfessional && $wellbeingTracker->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette entrée.');
        }

        if ($wellbeingTracker->getUser() === null) {
            $wellbeingTracker->setUser($user);
        }

        $form = $this->createForm(WellbeingTrackerType::class, $wellbeingTracker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($wellbeingTracker->getUser() === null) {
                $wellbeingTracker->setUser($user);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Entrée modifiée avec succès !');
            return $this->redirectToRoute('app_wellbeing_tracker_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('wellbeing_tracker/edit.html.twig', [
            'wellbeing_tracker' => $wellbeingTracker,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_wellbeing_tracker_delete', methods: ['POST'])]
    public function delete(Request $request, WellbeingTracker $wellbeingTracker, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to delete this entry
        if (!$isProfessional && $wellbeingTracker->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette entrée.');
        }

        if ($this->isCsrfTokenValid('delete' . $wellbeingTracker->getId(), $request->request->get('_token'))) {
            $entityManager->remove($wellbeingTracker);
            $entityManager->flush();
            $this->addFlash('success', 'Entrée supprimée avec succès !');
        }

        return $this->redirectToRoute('app_wellbeing_tracker_index', [], Response::HTTP_SEE_OTHER);
    }
}