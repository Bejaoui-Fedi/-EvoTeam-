<?php

namespace App\Controller;

use App\Entity\Objective;
use App\Repository\ExerciseRepository;
use App\Repository\ObjectiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/user/objectives')]
#[IsGranted('ROLE_USER')]
class UserExerciseController extends AbstractController
{
    // ─── LIST OBJECTIVES ─────────────────────────────────────────────────────
    #[Route('/', name: 'app_user_objective_list', methods: ['GET'])]
    public function list(Request $request, ObjectiveRepository $repo): Response
    {
        $q = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'title_az');

        $objectives = $repo->search($q, $sort, true); // published only

        return $this->render('user/objective/list.html.twig', [
            'objectives' => $objectives,
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    // ─── VIEW EXERCISES OF AN OBJECTIVE ──────────────────────────────────────
    #[Route('/{id}', name: 'app_user_objective_view', methods: ['GET'])]
    public function view(Request $request, Objective $objective, ExerciseRepository $exRepo, \App\Service\PredictiveService $predictive): Response
    {
        if (!$objective->isPublished()) {
            throw $this->createNotFoundException('Objectif non trouvé.');
        }

        $q = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'duration');

        $exercises = $exRepo->searchByObjective($objective, $q, $sort, true);

        $user = $this->getUser();
        $velocity = $predictive->calculateVelocity($user);
        $estimates = $predictive->estimateCompletionDate($user, $objective);

        return $this->render('user/objective/view.html.twig', [
            'objective' => $objective,
            'exercises' => $exercises,
            'q' => $q,
            'sort' => $sort,
            'velocity' => $velocity,
            'estimatedDate' => $estimates,
        ]);
    }

    // ─── EXPORT PDF ──────────────────────────────────────────────────────────
    #[Route('/{id}/export-pdf', name: 'app_user_objective_pdf', methods: ['GET'])]
    public function exportPdf(Objective $objective, ExerciseRepository $exRepo): Response
    {
        if (!$objective->isPublished()) {
            throw $this->createNotFoundException('Objectif non trouvé.');
        }

        $exercises = $exRepo->searchByObjective($objective, '', 'duration', true);

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
                'Content-Disposition' => 'attachment; filename="programme_bienetre.pdf"'
            ]
        );
    }

    // ─── EXERCISE DETAIL ─────────────────────────────────────────────────────
    #[Route('/exercise/{id}', name: 'app_user_exercise_detail', methods: ['GET'])]
    public function exerciseDetail(int $id, ExerciseRepository $exRepo): Response
    {
        $exercise = $exRepo->find($id);

        if (!$exercise || !$exercise->isPublished()) {
            throw $this->createNotFoundException('Exercice non trouvé.');
        }

        return $this->render('user/exercise/detail.html.twig', [
            'exercise' => $exercise,
        ]);
    }

    // ─── COMPLETE EXERCISE ───────────────────────────────────────────────────
    #[Route('/exercise/{id}/complete', name: 'app_user_exercise_complete', methods: ['POST'])]
    public function complete(int $id, ExerciseRepository $exRepo, \App\Service\PredictiveService $predictive): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $exercise = $exRepo->find($id);
        $user = $this->getUser();

        if (!$exercise || !$user) {
            return $this->json(['status' => 'error', 'message' => 'Erreur lors de la validation.'], 404);
        }

        $result = $predictive->recordCompletion($user, $exercise);

        return $this->json($result);
    }

    #[Route('/exercise/{id}/schedule', name: 'app_user_exercise_schedule', methods: ['POST'])]
    public function schedule(
        int $id,
        \Symfony\Component\HttpFoundation\Request $request,
        ExerciseRepository $exRepo,
        \Doctrine\ORM\EntityManagerInterface $em,
        \App\Service\GoogleCalendarService $calendar
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        $exercise = $exRepo->find($id);
        $scheduledAtStr = $request->request->get('scheduledAt');

        if (!$exercise || !$scheduledAtStr) {
            return $this->json(['success' => false, 'message' => 'Données manquantes.'], 400);
        }

        try {
            $start = new \DateTime($scheduledAtStr);
            // End is Start + Exercise Duration (default to 30min if null or 0)
            $duration = $exercise->getDurationMinutes() > 0 ? $exercise->getDurationMinutes() : 30;
            $end = clone $start;
            $end->modify("+{$duration} minutes");

            $summary = "Evolia : " . $exercise->getTitle();
            $description = "C'est l'heure de votre séance Evolia !\nExercice : " . $exercise->getTitle();

            // Use your specific Google Calendar email for synchronization
            $calendarId = 'mnassrimayssa4@gmail.com';
            $result = $calendar->createEvent($summary, $description, $start, $end, $calendarId);

            if ($result['success']) {
                $exercise->setCalendarEventId($result['id']);
                $em->persist($exercise);
                $em->flush();

                return $this->json(['success' => true]);
            }

            return $this->json(['success' => false, 'message' => $result['message']], 500);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
