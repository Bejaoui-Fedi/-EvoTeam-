<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\Objective;
use App\Repository\ExerciseRepository;
use App\Repository\ObjectiveRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\YouTubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/exercises')]
#[IsGranted('ROLE_ADMIN')]
class AdminExerciseController extends AbstractController
{
    // ─── LIST ALL  ───────────────────────────────────────────────────────────
    #[Route('/', name: 'app_admin_exercise_index', methods: ['GET'])]
    public function index(Request $request, ExerciseRepository $repo, ObjectiveRepository $objRepo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'title_az');

        return $this->render('admin/exercise/index.html.twig', [
            'exercises'  => $repo->searchAll($q, $sort),
            'objectives' => $objRepo->findAll(),
            'q'          => $q,
            'sort'       => $sort,
        ]);
    }

    // ─── NEW ─────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'app_admin_exercise_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ObjectiveRepository $objRepo): Response
    {
        $objectives = $objRepo->findAll();

        if ($request->isMethod('POST')) {
            $exercise = new Exercise();
            $exercise->setUser($this->getUser());

            $objId = (int) $request->request->get('objective');
            $objective = $objRepo->find($objId);
            if (!$objective) {
                $this->addFlash('error', 'Objectif invalide.');
                return $this->render('admin/exercise/new_edit.html.twig', [
                    'exercise' => new Exercise(), 'mode' => 'new', 'objectives' => $objectives
                ]);
            }

            $this->fillExercise($exercise, $request, $objective);

            if (empty($exercise->getTitle())) {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->render('admin/exercise/new_edit.html.twig', [
                    'exercise' => $exercise, 'mode' => 'new', 'objectives' => $objectives
                ]);
            }

            $em->persist($exercise);
            $em->flush();

            $this->addFlash('success', 'Exercice "' . $exercise->getTitle() . '" créé avec succès !');
            return $this->redirectToRoute('app_admin_objective_exercises', ['id' => $objective->getId()]);
        }

        // Pre-select objective if passed via query param
        $preObjectiveId = $request->query->get('objective_id');
        $preObjective   = $preObjectiveId ? $objRepo->find($preObjectiveId) : null;

        return $this->render('admin/exercise/new_edit.html.twig', [
            'exercise'     => new Exercise(),
            'mode'         => 'new',
            'objectives'   => $objectives,
            'preObjective' => $preObjective,
        ]);
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'app_admin_exercise_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exercise $exercise, EntityManagerInterface $em, ObjectiveRepository $objRepo): Response
    {
        $objectives = $objRepo->findAll();

        if ($request->isMethod('POST')) {
            $objId = (int) $request->request->get('objective');
            $objective = $objRepo->find($objId);
            if ($objective) {
                $exercise->setObjective($objective);
            }

            $this->fillExercise($exercise, $request, $exercise->getObjective());
            $exercise->setUpdatedAt(new \DateTime());

            if (empty($exercise->getTitle())) {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->render('admin/exercise/new_edit.html.twig', [
                    'exercise' => $exercise, 'mode' => 'edit', 'objectives' => $objectives
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Exercice "' . $exercise->getTitle() . '" mis à jour !');
            return $this->redirectToRoute('app_admin_objective_exercises', ['id' => $exercise->getObjective()->getId()]);
        }

        return $this->render('admin/exercise/new_edit.html.twig', [
            'exercise'   => $exercise,
            'mode'       => 'edit',
            'objectives' => $objectives,
        ]);
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'app_admin_exercise_delete', methods: ['POST'])]
    public function delete(Request $request, Exercise $exercise, EntityManagerInterface $em): Response
    {
        $objectiveId = $exercise->getObjective()?->getId();

        if ($this->isCsrfTokenValid('delete_ex_' . $exercise->getId(), $request->request->get('_token'))) {
            $title = $exercise->getTitle();
            $em->remove($exercise);
            $em->flush();
            $this->addFlash('success', 'Exercice "' . $title . '" supprimé.');
        }

        if ($objectiveId) {
            return $this->redirectToRoute('app_admin_objective_exercises', ['id' => $objectiveId]);
        }
        return $this->redirectToRoute('app_admin_exercise_index');
    }

    // ─── TOGGLE PUBLISH ──────────────────────────────────────────────────────
    #[Route('/{id}/toggle-publish', name: 'app_admin_exercise_toggle', methods: ['POST'])]
    public function togglePublish(Exercise $exercise, EntityManagerInterface $em): JsonResponse
    {
        $exercise->setIsPublished(!$exercise->isPublished());
        $exercise->setUpdatedAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['published' => $exercise->isPublished()]);
    }

    // ─── FETCH YOUTUBE VIDEO ─────────────────────────────────────────────────
    #[Route('/{id}/fetch-video', name: 'app_admin_exercise_fetch_video', methods: ['POST'])]
    public function fetchVideo(Exercise $exercise, EntityManagerInterface $em, YouTubeService $yt): JsonResponse
    {
        $videoUrl = $yt->searchVideoUrl($exercise->getTitle());

        if (!$videoUrl) {
            $apiKey = $_ENV['YOUTUBE_API_KEY'] ?? '';
            $error = ($apiKey === 'your_youtube_api_key' || empty($apiKey)) 
                ? 'Clé API YouTube non configurée dans le fichier .env.' 
                : 'Aucune vidéo adéquate trouvée sur YouTube.';
            return new JsonResponse(['error' => $error], 404);
        }

        $exercise->setMediaUrl($videoUrl);
        $exercise->setUpdatedAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['url' => $videoUrl]);
    }

    // ─── PRIVATE HELPER ──────────────────────────────────────────────────────
    private function fillExercise(Exercise $exercise, Request $request, ?Objective $objective): void
    {
        if ($objective) {
            $exercise->setObjective($objective);
        }
        $exercise->setTitle(trim($request->request->get('title', '')));
        $exercise->setDescription(trim($request->request->get('description', '')));
        $exercise->setType($request->request->get('type', 'physical'));
        $exercise->setDurationMinutes((int) $request->request->get('durationMinutes', 30));
        $exercise->setDifficulty($request->request->get('difficulty', 'debutant'));
        $exercise->setMediaUrl(trim($request->request->get('mediaUrl', '')) ?: null);
        $exercise->setSteps(trim($request->request->get('steps', '')) ?: null);
        $exercise->setIsPublished((bool) $request->request->get('isPublished', false));
    }
}
