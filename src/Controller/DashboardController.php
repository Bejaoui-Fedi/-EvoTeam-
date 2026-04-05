<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\DailyRoutineTaskRepository;
use App\Repository\WellbeingTrackerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        DailyRoutineTaskRepository $taskRepository,
        WellbeingTrackerRepository $wellbeingRepository
    ): Response {
        // Aggregate statistics
        $totalUsers = $userRepository->count([]);
        
        $totalTasks = $taskRepository->count([]);
        $completedTasks = $taskRepository->count(['isCompleted' => true]);
        
        // Fetch recent wellbeing data for chart (up to 7 days)
        $recentTrackers = $wellbeingRepository->findBy([], ['date' => 'DESC'], 14);
        
        $chartDates = [];
        $moodScores = [];
        $stressScores = [];
        
        // Reverse to show chronological left-to-right
        foreach (array_reverse($recentTrackers) as $tracker) {
            $dateStr = $tracker->getDate() ? $tracker->getDate()->format('M d') : 'Unknown';
            $chartDates[] = $dateStr;
            $moodScores[] = $tracker->getMood();
            $stressScores[] = $tracker->getStress();
        }

        // Averages calculation in PHP since dataset is small
        $allTrackers = $wellbeingRepository->findAll();
        $totalMood = 0;
        $totalStress = 0;
        foreach ($allTrackers as $wt) {
            $totalMood += $wt->getMood();
            $totalStress += $wt->getStress();
        }
        $avgMood = count($allTrackers) ? round($totalMood / count($allTrackers), 1) : 0;
        $avgStress = count($allTrackers) ? round($totalStress / count($allTrackers), 1) : 0;

        return $this->render('dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'avgMood' => $avgMood,
            'avgStress' => $avgStress,
            'chartDates' => json_encode($chartDates),
            'moodScores' => json_encode($moodScores),
            'stressScores' => json_encode($stressScores),
        ]);
    }
}
