<?php

namespace App\Controller;

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
    public function index(WellbeingTrackerRepository $wellbeingTrackerRepository): Response
    {
        return $this->render('wellbeing_tracker/index.html.twig', [
            'wellbeing_trackers' => $wellbeingTrackerRepository->findAllWithRelations(),
        ]);
    }

    #[Route('/new', name: 'app_wellbeing_tracker_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $wellbeingTracker = new WellbeingTracker();
        $wellbeingTracker->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $form = $this->createForm(WellbeingTrackerType::class, $wellbeingTracker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($wellbeingTracker);
            $entityManager->flush();

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
        try { if ($wellbeingTracker->getUser()) $wellbeingTracker->getUser()->getNom(); } catch (\Exception $e) { $wellbeingTracker->setUser(null); }
        try { if ($wellbeingTracker->getRoutineTask()) $wellbeingTracker->getRoutineTask()->getTitle(); } catch (\Exception $e) { $wellbeingTracker->setRoutineTask(null); }

        return $this->render('wellbeing_tracker/show.html.twig', [
            'wellbeing_tracker' => $wellbeingTracker,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_wellbeing_tracker_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WellbeingTracker $wellbeingTracker, EntityManagerInterface $entityManager): Response
    {
        try { if ($wellbeingTracker->getUser()) $wellbeingTracker->getUser()->getNom(); } catch (\Exception $e) { $wellbeingTracker->setUser(null); }
        try { if ($wellbeingTracker->getRoutineTask()) $wellbeingTracker->getRoutineTask()->getTitle(); } catch (\Exception $e) { $wellbeingTracker->setRoutineTask(null); }

        $form = $this->createForm(WellbeingTrackerType::class, $wellbeingTracker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
        if ($this->isCsrfTokenValid('delete'.$wellbeingTracker->getId(), $request->request->get('_token'))) {
            $entityManager->remove($wellbeingTracker);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_wellbeing_tracker_index', [], Response::HTTP_SEE_OTHER);
    }
}
