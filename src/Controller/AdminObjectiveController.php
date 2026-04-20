<?php

namespace App\Controller;

use App\Entity\Objective;
use App\Entity\Exercise;
use App\Repository\ObjectiveRepository;
use App\Repository\ExerciseRepository;
use App\Service\YouTubeService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\AIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/objectives')]
#[IsGranted('ROLE_ADMIN')]
class AdminObjectiveController extends AbstractController
{
    // ─── LIST (with search + sort) ───────────────────────────────────────────
    #[Route('/', name: 'app_admin_objective_index', methods: ['GET'])]
    public function index(Request $request, ObjectiveRepository $repo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'title_az');

        $objectives = $repo->search($q, $sort, false);

        // Insight Statistics
        $totalObj = count($objectives);
        $totalEx = 0;
        $levels = ['debutant' => 0, 'moyen' => 0, 'avance' => 0, 'global' => 0];
        foreach ($objectives as $o) {
            $totalEx += count($o->getExercises());
            $l = strtolower($o->getLevel() ?? 'global');
            if (isset($levels[$l])) $levels[$l]++;
        }
        $avgEx = $totalObj > 0 ? round($totalEx / $totalObj, 1) : 0;

        $insights = [
            'totalEx' => $totalEx,
            'avgEx' => $avgEx,
            'levels' => $levels
        ];

        return $this->render('admin/objective/index.html.twig', [
            'objectives' => $objectives,
            'insights'   => $insights,
            'q'          => $q,
            'sort'       => $sort,
        ]);
    }

    // ─── NEW ─────────────────────────────────────────────────────────────────
    #[Route('/new', name: 'app_admin_objective_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $objective = new Objective();
            $objective->setUser($this->getUser());
            $objective->setTitle(trim($request->request->get('title', '')));
            $objective->setDescription(trim($request->request->get('description', '')));
            $objective->setLevel($request->request->get('level', 'debutant'));
            $objective->setIsPublished((bool) $request->request->get('isPublished', false));

            if (empty($objective->getTitle())) {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->render('admin/objective/new_edit.html.twig', [
                    'objective' => $objective, 'mode' => 'new'
                ]);
            }

            $em->persist($objective);
            $em->flush();

            $this->addFlash('success', 'Objectif "' . $objective->getTitle() . '" créé avec succès !');
            return $this->redirectToRoute('app_admin_objective_index');
        }

        return $this->render('admin/objective/new_edit.html.twig', [
            'objective' => new Objective(),
            'mode'      => 'new',
        ]);
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'app_admin_objective_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Objective $objective, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $objective->setTitle(trim($request->request->get('title', '')));
            $objective->setDescription(trim($request->request->get('description', '')));
            $objective->setLevel($request->request->get('level', 'debutant'));
            $objective->setIsPublished((bool) $request->request->get('isPublished', false));
            $objective->setUpdatedAt(new \DateTime());

            if (empty($objective->getTitle())) {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->render('admin/objective/new_edit.html.twig', [
                    'objective' => $objective, 'mode' => 'edit'
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Objectif "' . $objective->getTitle() . '" mis à jour !');
            return $this->redirectToRoute('app_admin_objective_index');
        }

        return $this->render('admin/objective/new_edit.html.twig', [
            'objective' => $objective,
            'mode'      => 'edit',
        ]);
    }

    // ─── VIEW EXERCISES of an Objective ──────────────────────────────────────
    #[Route('/{id}/exercises', name: 'app_admin_objective_exercises', methods: ['GET'])]
    public function exercises(Request $request, Objective $objective, ExerciseRepository $exRepo): Response
    {
        $q    = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'title_az');

        $exercises = $exRepo->searchByObjective($objective, $q, $sort, false);

        return $this->render('admin/objective/exercises.html.twig', [
            'objective' => $objective,
            'exercises' => $exercises,
            'q'         => $q,
            'sort'      => $sort,
        ]);
    }

    // ─── EXPORT PDF ──────────────────────────────────────────────────────────
    #[Route('/{id}/export-pdf', name: 'app_admin_objective_pdf', methods: ['GET'])]
    public function exportPdf(Objective $objective, ExerciseRepository $exRepo): Response
    {
        $exercises = $exRepo->searchByObjective($objective, '', 'title_az', false);

        $html = $this->renderView('pdf/objective.html.twig', [
            'objective' => $objective,
            'exercises' => $exercises,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="admin_programme_' . $objective->getId() . '.pdf"'
            ]
        );
    }

    // ─── AI EXERCISES PREVIEW ─────────────────────────────────────────────
    #[Route('/{id}/preview-ai', name: 'app_admin_objective_preview_ai', methods: ['POST'])]
    public function previewAiExercises(Objective $objective, AIService $aiService, YouTubeService $ytService): JsonResponse
    {
        try {
            $exercises = $aiService->generateExercisesForObjective($objective);
            
            $data = [];
            foreach ($exercises as $ex) {
                // Try to find a YouTube video for the title (like in Java)
                $videoUrl = $ytService->searchVideoUrl($ex->getTitle());
                
                $data[] = [
                    'title'       => $ex->getTitle(),
                    'description' => $ex->getDescription(),
                    'type'        => $ex->getType(),
                    'duration'    => $ex->getDurationMinutes(),
                    'difficulty'  => $ex->getDifficulty(),
                    'steps'       => $ex->getSteps(),
                    'mediaUrl'    => $videoUrl
                ];
            }

            return new JsonResponse(['success' => true, 'exercises' => $data]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── AI EXERCISES SAVE ────────────────────────────────────────────────
    #[Route('/{id}/save-ai', name: 'app_admin_objective_save_ai', methods: ['POST'])]
    public function saveAiExercises(Objective $objective, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            $selected = $payload['exercises'] ?? [];

            if (empty($selected)) {
                return new JsonResponse(['success' => false, 'error' => 'Aucun exercice sélectionné.']);
            }

            foreach ($selected as $exData) {
                $exercise = new Exercise();
                $exercise->setTitle($exData['title']);
                $exercise->setDescription($exData['description']);
                $exercise->setType($exData['type']);
                $exercise->setDurationMinutes((int)$exData['duration']);
                $exercise->setDifficulty($exData['difficulty']);
                $exercise->setSteps($exData['steps']);
                $exercise->setMediaUrl($exData['mediaUrl'] ?? null);
                $exercise->setIsPublished(1);
                $exercise->setObjective($objective);
                $exercise->setUser($this->getUser());
                $em->persist($exercise);
            }
            $em->flush();

            $this->addFlash('success', count($selected) . ' exercices générés ont été enregistrés !');
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'app_admin_objective_delete', methods: ['POST'])]
    public function delete(Request $request, Objective $objective, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_obj_' . $objective->getId(), $request->request->get('_token'))) {
            $title = $objective->getTitle();
            $em->remove($objective);
            $em->flush();
            $this->addFlash('success', 'Objectif "' . $title . '" supprimé.');
        }

        return $this->redirectToRoute('app_admin_objective_index');
    }

    // ─── TOGGLE PUBLISH ──────────────────────────────────────────────────────
    #[Route('/{id}/toggle-publish', name: 'app_admin_objective_toggle', methods: ['POST'])]
    public function togglePublish(Objective $objective, EntityManagerInterface $em): JsonResponse
    {
        $objective->setIsPublished(!$objective->isPublished());
        $objective->setUpdatedAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['published' => $objective->isPublished()]);
    }
}
