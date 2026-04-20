<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\HabitAiService;
use App\Repository\UserRepository;
use App\Repository\WellbeingTrackerRepository;
use App\Repository\DailyRoutineTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ai-coach')]
class AiCoachController extends AbstractController
{
    private $habitAiService;
    private $userRepository;
    private $wellbeingRepository;
    private $routineRepository;

    public function __construct(
        HabitAiService $habitAiService,
        UserRepository $userRepository,
        WellbeingTrackerRepository $wellbeingRepository,
        DailyRoutineTaskRepository $routineRepository
    ) {
        $this->habitAiService = $habitAiService;
        $this->userRepository = $userRepository;
        $this->wellbeingRepository = $wellbeingRepository;
        $this->routineRepository = $routineRepository;
    }

    #[Route('/', name: 'app_ai_coach_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Block Admins from AI Health Hub entirely
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Les administrateurs n\'ont pas accès au AI Health Hub.');
        }

        $isSpecialist = $this->isGranted('ROLE_COACH') || 
                        $this->isGranted('ROLE_PSYCHOLOGUE');

        // Determine which user's data to view
        $patientId = $request->query->get('patientId');
        $viewedUser = $currentUser;
        
        if ($isSpecialist && $patientId) {
            $viewedUser = $this->userRepository->find($patientId) ?? $currentUser;
        }

        // Fetch Habit AI data
        $token = $viewedUser->getHabitAiToken();        $today = date('Y-m-d');
        $requestedDate = $request->query->get('date', $today);
        $isToday = $requestedDate === date('Y-m-d');
        
        // Fetch Daily Summary
        $nutritionResponse = $this->habitAiService->getDailyNutrition($isToday ? null : $requestedDate, $token);
        // NEW: Fetch individual meals for fallback summation
        $mealsResponse = $this->habitAiService->getMeals($requestedDate, $token);
        
        $profile = $this->habitAiService->getProfile($token);
        $stepsResponse = $this->habitAiService->getSteps(7, $token);
        $weightResponse = $this->habitAiService->getWeight(30, $token);

        // Normalize Summary Data
        $nutrition = $nutritionResponse['summary'] ?? 
                    $nutritionResponse['totals'] ?? 
                    $nutritionResponse['dailyLog'] ?? 
                    $nutritionResponse['data'] ?? 
                    $nutritionResponse;

        if (is_array($nutrition) && !isset($nutrition['calories']) && isset($nutrition[0])) {
            $nutrition = $nutrition[0];
        }

        // --- NEW: Manual Summation Logic ---
        $meals = $mealsResponse['data'] ?? $mealsResponse['meals'] ?? $mealsResponse ?? [];
        $manualTotals = [
            'calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0,
            'fiber' => 0, 'sugar' => 0, 'sodium' => 0
        ];

        if (is_array($meals)) {
            foreach ($meals as $meal) {
                $manualTotals['calories'] += (float)($meal['calories'] ?? $meal['totalCalories'] ?? 0);
                $manualTotals['protein'] += (float)($meal['protein'] ?? $meal['totalProtein'] ?? 0);
                $manualTotals['carbs'] += (float)($meal['carbs'] ?? $meal['totalCarbs'] ?? 0);
                $manualTotals['fat'] += (float)($meal['fat'] ?? $meal['totalFat'] ?? 0);
                $manualTotals['fiber'] += (float)($meal['fiber'] ?? $meal['totalFiber'] ?? 0);
                $manualTotals['sugar'] += (float)($meal['sugar'] ?? $meal['totalSugar'] ?? 0);
                $manualTotals['sodium'] += (float)($meal['sodium'] ?? $meal['totalSodium'] ?? 0);
            }
        }

        // Map keys recursively if needed to ensure compatibility with template
        if ($nutrition || count($meals) > 0) {
            if (!$nutrition) $nutrition = [];
            
            // Fuzzy search helper
            $fuzzyFind = function($obj, $needle) use (&$fuzzyFind) {
                if (!is_array($obj)) return null;
                foreach ($obj as $k => $v) {
                    if (is_numeric($v)) {
                        if (str_contains(strtolower($k), strtolower($needle))) return $v;
                    } elseif (is_array($v)) {
                        $res = $fuzzyFind($v, $needle);
                        if ($res !== null) return $res;
                    }
                }
                return null;
            };

            $extractValue = function($obj, $keys) {
                if (!is_array($obj)) return null;
                foreach ($keys as $k) {
                    if (isset($obj[$k])) {
                        if (is_numeric($obj[$k])) return $obj[$k];
                        if (is_array($obj[$k])) {
                            foreach (['value', 'total', 'amount', 'totalCalories', 'waterIntake'] as $sub) {
                                if (isset($obj[$k][$sub]) && is_numeric($obj[$k][$sub])) return $obj[$k][$sub];
                            }
                        }
                    }
                }
                return null;
            };

            // Merge Manual Totals if they are higher than summary (Fallback)
            $calorieKeys = ['calories', 'totalCalories', 'total_calories', 'kcal'];
            $summaryCals = (float)($extractValue($nutrition, $calorieKeys) ?? $fuzzyFind($nutrition, 'kcal') ?? 0);
            $nutrition['totalCalories'] = max($summaryCals, $manualTotals['calories']);
            
            $waterKeys = ['waterIntake', 'water', 'amount'];
            $nutrition['waterIntake'] = (float)($extractValue($nutrition, $waterKeys) ?? $fuzzyFind($nutrition, 'water') ?? 0);

            // Robust Macro & Micro mapping with fallback
            $nutrients = [
                'protein' => ['protein', 'totalProtein', 'total_protein', 'proteinInGrams'],
                'carbs' => ['carbs', 'totalCarbs', 'total_carbs', 'carbsInGrams'],
                'fat' => ['fat', 'totalFat', 'total_fat', 'fatInGrams'],
                'fiber' => ['fiber', 'totalFiber', 'total_fiber', 'fiberInGrams'],
                'sugar' => ['sugar', 'totalSugar', 'total_sugar', 'sugarInGrams'],
                'sodium' => ['sodium', 'totalSodium', 'total_sodium', 'sodiumInMilligrams']
            ];

            foreach ($nutrients as $key => $keys) {
                $summaryVal = (float)($extractValue($nutrition, $keys) ?? $fuzzyFind($nutrition, $key) ?? 0);
                $nutrition[$key] = max($summaryVal, $manualTotals[$key]);
            }
        }


        // Normalize data structures from API response
        $steps = $stepsResponse['steps'] ?? $stepsResponse;
        $weight = $weightResponse['weight'] ?? $weightResponse;

        // Extract today's steps (Check Profile first, then History)
        $profileSteps = $profile['steps'] ?? $profile['currentSteps'] ?? $profile['todaySteps'] ?? 0;
        $historySteps = 0;

        if (is_array($steps) && !isset($steps['error'])) {
            foreach ($steps as $entry) {
                if (isset($entry['date'])) {
                    try {
                        $entryDT = new \DateTime($entry['date']);
                        // Fuzzy check for today
                        $isToday = ($entryDT->format('Y-m-d') === $today || 
                                   ($entryDT->modify('+4 hours')->format('Y-m-d') === $today));

                        if ($isToday) {
                            // Fuzzy key detection within history entry
                            $historySteps = (int)($entry['steps'] ?? $entry['count'] ?? $entry['amount'] ?? $entry['total'] ?? 0);
                            break;
                        }
                    } catch (\Exception $e) {}
                }
            }
        }
        
        $currentSteps = max((int)$profileSteps, (int)$historySteps);

        // Extract latest weight from history if available
        $currentWeight = $profile['weightInKg'] ?? '--';
        if (is_array($weight) && !isset($weight['error'])) {
            usort($weight, function ($a, $b) {
                return strcmp($b['date'] ?? '', $a['date'] ?? '');
            });
            $currentWeight = $weight[0]['weightInKg'] ?? $currentWeight;
        }

        // Local tracking data (for the specialist view and AI analysis)
        $localWellbeing = $this->wellbeingRepository->findBy(['user' => $viewedUser], ['date' => 'DESC'], 5);
        $localRoutines = $this->routineRepository->findBy(['user' => $viewedUser], ['createdAt' => 'DESC'], 50);

        // --- NEW: Daily Wellness Advances Logic ---
        $advancesData = [];
        $todayDT = new \DateTime($today);
        
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = (new \DateTime($today))->modify("-$i days");
            $dateStr = $dateObj->format('Y-m-d');
            
            // 1. Task Score (40 pts)
            $tasksOnDay = array_filter($localRoutines, function($t) use ($dateStr) {
                try {
                    $createdDate = new \DateTime($t->getCreatedAt() ?? 'now');
                    return $createdDate->format('Y-m-d') === $dateStr;
                } catch (\Exception $e) {
                    return false;
                }
            });
            $taskScore = 0;
            if (!empty($tasksOnDay)) {
                $completedOnDay = count(array_filter($tasksOnDay, function($t) { return $t->isIsCompleted(); }));
                $taskScore = ($completedOnDay / count($tasksOnDay)) * 40;
            }

            // 2. Step Score (40 pts)
            $stepsOnDay = 0;
            if (is_array($steps)) {
                foreach ($steps as $entry) {
                    if (isset($entry['date'])) {
                        $entryDT = new \DateTime($entry['date']);
                        if ($entryDT->format('Y-m-d') === $dateStr) {
                            $stepsOnDay = (int)($entry['steps'] ?? 0);
                            break;
                        }
                    }
                }
            }
            $stepScore = min(40, ($stepsOnDay / ($profile['stepsGoal'] ?? 5000 ?: 5000)) * 40);

            // 3. Water Score (20 pts - only today's data is reliably detailed for history for now)
            // If it's today, use actual. If past, use high probability estimate or 0.
            $waterScore = 0;
            if ($dateStr === $today) {
                $waterScore = min(20, (($nutrition['waterIntake'] ?? 0) / 2000) * 20);
            } elseif ($stepsOnDay > 0 || !empty($tasksOnDay)) {
                $waterScore = 10; // Nominal value for active historical days
            }

            $advancesData[] = [
                'day' => $dateObj->format('D d M'),
                'score' => round($taskScore + $stepScore + $waterScore)
            ];
        }

        // --- NEW: Mental Health Analysis Generation ---
        $completedTasks = 0;
        $totalTasksToday = count(array_filter($localRoutines, function($t) use ($today) {
            try {
                return (new \DateTime($t->getCreatedAt() ?? 'now'))->format('Y-m-d') === $today;
            } catch (\Exception $e) { return false; }
        }));

        foreach ($localRoutines as $task) {
            try {
                $cDate = new \DateTime($task->getCreatedAt() ?? 'now');
                if ($cDate->format('Y-m-d') === $today && $task->isIsCompleted()) {
                    $completedTasks++;
                }
            } catch (\Exception $e) {}
        }
        
        $analysisPrompt = sprintf(
            "Analyse l'état de bien-être de l'utilisateur basé sur ces données d'aujourd'hui : " .
            "Routines : %d/%d terminées. Eau : %dml. Poids : %s kg. " .
            "Pas récents : %d. " .
            "Fournis une analyse de santé mentale très brève (2 phrases) et un conseil encourageant.",
            $completedTasks, $totalTasksToday, $nutrition['waterIntake'] ?? 0, $currentWeight, $currentSteps
        );

        $analysisResponse = $this->habitAiService->sendMessage('mindfulness', $analysisPrompt, [], $token);
        $mentalHealthAnalysis = $analysisResponse['message'] ?? "Continuez vos efforts pour maintenir un équilibre sain entre activité et hydratation.";
        // ----------------------------------------------

        // All patients for the search bar (only for specialists)
        $patients = [];
        if ($isSpecialist) {
            $patients = $this->userRepository->createQueryBuilder('u')
                ->where('u.role LIKE :p1 OR u.role LIKE :p2 OR u.role = :p3')
                ->setParameter('p1', '%PATIENT%')
                ->setParameter('p2', '%patient%')
                ->setParameter('p3', 'ROLE_USER') // Sometimes basic users are patients
                ->orderBy('u.nom', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('ai_coach/index.html.twig', [
            'viewed_user' => $viewedUser,
            'is_viewing_self' => ($viewedUser->getId() === $currentUser->getId()),
            'is_specialist' => $isSpecialist,
            'profile' => $profile,
            'steps_data' => $steps,
            'current_steps' => $currentSteps,
            'weight_data' => $weight,
            'current_weight' => $currentWeight,
            'nutrition_data' => $nutrition,
            'local_wellbeing' => $localWellbeing,
            'local_routines' => array_slice($localRoutines, 0, 5),
            'patients' => $patients,
            'mental_health_analysis' => $mentalHealthAnalysis,
            'advances_data' => $advancesData,
        ]);
    }

    #[Route('/chat', name: 'app_ai_coach_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Only owner can chat
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $coach = $data['coach'] ?? 'mindfulness';
        $history = $data['history'] ?? [];

        if (!$message) {
            return new JsonResponse(['error' => 'Message vide.'], 400);
        }

        $response = $this->habitAiService->sendMessage($coach, $message, $history, $user->getHabitAiToken());

        return new JsonResponse($response);
    }

    #[Route('/health-metric-update', name: 'app_ai_coach_log', methods: ['POST'])]
    public function log(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();

        // Support both JSON and Form Post
        $data = json_decode($request->getContent(), true) ?? [];
        $type = $request->request->get('type', $data['type'] ?? '');
        $value = $request->request->get('value', $data['value'] ?? null);
        $date = $request->request->get('date', $data['date'] ?? date('Y-m-d'));
        $isRedirect = $request->request->has('redirect');

        if (!$type || (is_null($value) && $type !== 'nutrition_bulk')) {
            if ($isRedirect) {
                $this->addFlash('error', 'Données manquantes.');
                return $this->redirectToRoute('app_ai_coach_index');
            }
            return new JsonResponse(['error' => 'Données manquantes.'], 400);
        }

        $response = match ($type) {
            'steps' => $this->habitAiService->logSteps((int)$value, $date, $user->getHabitAiToken()),
            'weight' => $this->habitAiService->logWeight((float)$value, $date, $user->getHabitAiToken()),
            'water' => $this->habitAiService->logWater((int)$value, $date, $user->getHabitAiToken()),
            'calories' => $this->habitAiService->logCalories((int)$value, $date, $user->getHabitAiToken()),
            'meal_description' => $this->habitAiService->logMealDescription((string)$value, $date, $user->getHabitAiToken()),
            'protein' => $this->habitAiService->logNutrition(['protein' => (int)$value], $date, $user->getHabitAiToken()),
            'carbs' => $this->habitAiService->logNutrition(['carbs' => (int)$value], $date, $user->getHabitAiToken()),
            'fat' => $this->habitAiService->logNutrition(['fat' => (int)$value], $date, $user->getHabitAiToken()),
            'nutrition_bulk' => $this->habitAiService->logMeal([
                'mealName' => $request->request->get('mealName', 'Manual Log'),
                'calories' => (int)($request->request->get('calories', $data['calories'] ?? 0)),
                'protein' => (int)($request->request->get('protein', $data['protein'] ?? 0)),
                'carbs' => (int)($request->request->get('carbs', $data['carbs'] ?? 0)),
                'fat' => (int)($request->request->get('fat', $data['fat'] ?? 0)),
                'fiber' => (int)($request->request->get('fiber', $data['fiber'] ?? 0)),
                'sugar' => (int)($request->request->get('sugar', $data['sugar'] ?? 0)),
                'sodium' => (int)($request->request->get('sodium', $data['sodium'] ?? 0)),
                'mealType' => 'snack',
            ], $user->getHabitAiToken()),
            default => ['error' => 'Type de métrique invalide.'],
        };

        if (isset($response['error'])) {
            if ($isRedirect) {
                $this->addFlash('error', $response['error']);
                return $this->redirectToRoute('app_ai_coach_index');
            }
            return new JsonResponse($response, 400);
        }

        if ($isRedirect) {
            $this->addFlash('success', 'Métrique enregistrée avec succès !');
            return $this->redirectToRoute('app_ai_coach_index');
        }

        return new JsonResponse($response);
    }
}
