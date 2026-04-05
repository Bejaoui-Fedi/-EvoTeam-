<?php

namespace App\Controller;

use App\Entity\DailyRoutineTask;
use App\Form\DailyRoutineTaskType;
use App\Repository\DailyRoutineTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/daily-routine')]
class DailyRoutineTaskController extends AbstractController
{
    #[Route('/', name: 'app_daily_routine_task_index', methods: ['GET'])]
    public function index(DailyRoutineTaskRepository $dailyRoutineTaskRepository): Response
    {
        return $this->render('daily_routine_task/index.html.twig', [
            'daily_routine_tasks' => $dailyRoutineTaskRepository->findAllWithUsers(),
        ]);
    }

    #[Route('/new', name: 'app_daily_routine_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dailyRoutineTask = new DailyRoutineTask();
        $dailyRoutineTask->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $form = $this->createForm(DailyRoutineTaskType::class, $dailyRoutineTask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dailyRoutineTask->isIsCompleted() && !$dailyRoutineTask->getCompletedAt()) {
                $dailyRoutineTask->setCompletedAt(new \DateTime());
            } elseif (!$dailyRoutineTask->isIsCompleted()) {
                $dailyRoutineTask->setCompletedAt(null);
            }
            $entityManager->persist($dailyRoutineTask);
            $entityManager->flush();

            return $this->redirectToRoute('app_daily_routine_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('daily_routine_task/new.html.twig', [
            'daily_routine_task' => $dailyRoutineTask,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_daily_routine_task_show', methods: ['GET'])]
    public function show(DailyRoutineTask $dailyRoutineTask): Response
    {
        try {
            if ($dailyRoutineTask->getUser()) {
                $dailyRoutineTask->getUser()->getNom();
            }
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $dailyRoutineTask->setUser(null);
        }

        return $this->render('daily_routine_task/show.html.twig', [
            'daily_routine_task' => $dailyRoutineTask,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_daily_routine_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DailyRoutineTask $dailyRoutineTask, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($dailyRoutineTask->getUser()) {
                $dailyRoutineTask->getUser()->getNom();
            }
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            $dailyRoutineTask->setUser(null);
        }

        $form = $this->createForm(DailyRoutineTaskType::class, $dailyRoutineTask);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dailyRoutineTask->isIsCompleted() && !$dailyRoutineTask->getCompletedAt()) {
                $dailyRoutineTask->setCompletedAt(new \DateTime());
            } elseif (!$dailyRoutineTask->isIsCompleted()) {
                $dailyRoutineTask->setCompletedAt(null);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_daily_routine_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('daily_routine_task/edit.html.twig', [
            'daily_routine_task' => $dailyRoutineTask,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_daily_routine_task_delete', methods: ['POST'])]
    public function delete(Request $request, DailyRoutineTask $dailyRoutineTask, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dailyRoutineTask->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dailyRoutineTask);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_daily_routine_task_index', [], Response::HTTP_SEE_OTHER);
    }
}
