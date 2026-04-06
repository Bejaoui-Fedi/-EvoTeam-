<?php

namespace App\Controller;

use App\Entity\DailyRoutineTask;
use App\Entity\User;
use App\Form\DailyRoutineTaskType;
use App\Repository\DailyRoutineTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/daily-routine')]
class DailyRoutineTaskController extends AbstractController
{
    #[Route('/', name: 'app_daily_routine_task_index', methods: ['GET'])]
    public function index(Request $request, DailyRoutineTaskRepository $dailyRoutineTaskRepository): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Get search term from query string
        $searchTerm = $request->query->get('search');

        // Get tasks based on user role
        if ($isProfessional) {
            // Professionals see all tasks
            $allTasks = $dailyRoutineTaskRepository->findAllWithUsers();

            // Get filtered tasks based on search
            if ($searchTerm) {
                $dailyRoutineTasks = $dailyRoutineTaskRepository->createQueryBuilder('t')
                    ->leftJoin('t.user', 'u')
                    ->addSelect('u')
                    ->where('t.title LIKE :search')
                    ->orWhere('u.nom LIKE :search')
                    ->orWhere('u.email LIKE :search')
                    ->setParameter('search', '%' . $searchTerm . '%')
                    ->orderBy('t.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                $dailyRoutineTasks = $allTasks;
            }
        } else {
            // Regular users see only their own tasks
            $allTasks = $dailyRoutineTaskRepository->findBy(['user' => $user]);

            if ($searchTerm) {
                $dailyRoutineTasks = $dailyRoutineTaskRepository->createQueryBuilder('t')
                    ->where('t.user = :user')
                    ->andWhere('t.title LIKE :search')
                    ->setParameter('user', $user)
                    ->setParameter('search', '%' . $searchTerm . '%')
                    ->orderBy('t.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                $dailyRoutineTasks = $allTasks;
            }
        }

        // Calculate statistics
        $totalTasks = count($allTasks);
        $completedTasks = 0;
        foreach ($allTasks as $task) {
            if ($task->isIsCompleted()) {
                $completedTasks++;
            }
        }
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return $this->render('daily_routine_task/index.html.twig', [
            'daily_routine_tasks' => $dailyRoutineTasks,
            'all_tasks' => $allTasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'completionRate' => $completionRate,
            'searchTerm' => $searchTerm,
            'isProfessional' => $isProfessional,
        ]);
    }

    #[Route('/new', name: 'app_daily_routine_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $dailyRoutineTask = new DailyRoutineTask();
        $dailyRoutineTask->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $dailyRoutineTask->setUser($user);

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

            $this->addFlash('success', 'Tâche créée avec succès!');
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
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to view this task
        if (!$isProfessional && $dailyRoutineTask->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas voir cette tâche.');
        }

        return $this->render('daily_routine_task/show.html.twig', [
            'daily_routine_task' => $dailyRoutineTask,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_daily_routine_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DailyRoutineTask $dailyRoutineTask, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to edit this task
        if (!$isProfessional && $dailyRoutineTask->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette tâche.');
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

            $this->addFlash('success', 'Tâche modifiée avec succès!');
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
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $isProfessional = $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_COACH') ||
            $this->isGranted('ROLE_PSYCHOLOGUE');

        // Check if user has permission to delete this task
        if (!$isProfessional && $dailyRoutineTask->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette tâche.');
        }

        if ($this->isCsrfTokenValid('delete' . $dailyRoutineTask->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dailyRoutineTask);
            $entityManager->flush();
            $this->addFlash('success', 'Tâche supprimée avec succès!');
        }

        return $this->redirectToRoute('app_daily_routine_task_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_daily_routine_task_complete', methods: ['POST'])]
    public function complete(Request $request, DailyRoutineTask $task, EntityManagerInterface $entityManager): Response
    {
        // Allow only authenticated users
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Only the task owner can mark it as complete
        if ($task->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette tâche.');
        }

        $task->setIsCompleted(true);
        $task->setCompletedAt(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Tâche marquée comme complétée !');
        return $this->redirectToRoute('app_daily_routine_task_index');
    }
}