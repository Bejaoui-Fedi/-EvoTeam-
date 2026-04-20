<?php

namespace App\Service;

use App\Entity\Exercise;
use App\Entity\ExerciseCompletion;
use App\Entity\Objective;
use App\Entity\User;
use App\Repository\ExerciseCompletionRepository;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PredictiveService
{
    private EntityManagerInterface $em;
    private ExerciseCompletionRepository $completionRepository;
    private ExerciseRepository $exerciseRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        ExerciseCompletionRepository $completionRepository,
        ExerciseRepository $exerciseRepository,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->completionRepository = $completionRepository;
        $this->exerciseRepository = $exerciseRepository;
        $this->logger = $logger;
    }

    /**
     * Records an exercise completion and updates user XP/Streak
     */
    public function recordCompletion(User $user, Exercise $exercise): array
    {
        $objective = $exercise->getObjective();
        if (!$objective) {
            return ['status' => 'error', 'message' => 'Exercice sans objectif associé.'];
        }

        // 1. Create Completion Record
        $completion = new ExerciseCompletion();
        $completion->setUser($user);
        $completion->setExercise($exercise);
        $completion->setObjective($objective);
        $this->em->persist($completion);

        // 2. Update XP (50 XP per exercise as per Java logic)
        $xpGained = 50;
        $user->setXp($user->getXp() + $xpGained);

        // 3. Update Level based on XP
        $user->setLevel($this->calculateLevel($user->getXp()));

        // 4. Update Streak
        $streakMessage = $this->updateStreak($user);

        $this->em->flush();

        return [
            'status' => 'success',
            'xpGained' => $xpGained,
            'newLevel' => $user->getLevel(),
            'streak' => $user->getCurrentStreak(),
            'message' => $streakMessage
        ];
    }

    /**
     * Estimates the completion date for an objective based on user velocity
     */
    public function estimateCompletionDate(User $user, Objective $objective): ?\DateTimeImmutable
    {
        $userId = $user->getId();
        if (!$userId) return null;

        // 1. Get total exercises in objective
        $totalExercises = count($this->exerciseRepository->findBy(['objective' => $objective, 'isPublished' => true]));
        if ($totalExercises === 0) return null;

        // 2. Get completed exercises for this objective
        $completedCount = $this->completionRepository->count(['user' => $user, 'objective' => $objective]);
        $remaining = $totalExercises - $completedCount;

        if ($remaining <= 0) return new \DateTimeImmutable(); // Already finished

        // 3. Calculate Velocity (avg exercises per day in last 7 days)
        $velocity = $this->calculateVelocity($user);
        if ($velocity <= 0) return null; // Cannot predict without activity

        $daysToComplete = ceil($remaining / $velocity);
        
        return (new \DateTimeImmutable())->modify("+$daysToComplete days");
    }

    /**
     * Detects if the user is at risk of "churn" (inactivity > 3 days)
     */
    public function getChurnRisk(User $user): ?array
    {
        $lastActivity = $user->getLastActivityDate();
        if (!$lastActivity) return null;

        $today = new \DateTimeImmutable();
        $diff = $today->diff($lastActivity)->days;

        if ($diff >= 3) {
            return [
                'daysInactive' => $diff,
                'level' => $diff >= 7 ? 'High' : 'Medium',
                'message' => "Vous n'avez rien complété depuis $diff jours. Ne perdez pas votre élan !"
            ];
        }

        return null;
    }

    public function calculateVelocity(User $user): float
    {
        $recent = $this->completionRepository->getRecentActivity($user->getId(), 7);
        if (empty($recent)) return 0.0;

        $totalCompletions = 0;
        foreach ($recent as $day) {
            $totalCompletions += $day['total'];
        }

        return $totalCompletions / 7.0;
    }

    private function calculateLevel(int $xp): string
    {
        if ($xp < 100) return 'DEBUTANT';
        if ($xp < 300) return 'HABITUE';
        if ($xp < 600) return 'ATHLETE';
        if ($xp < 1000) return 'EXPERT';
        return 'MAITRE';
    }

    private function updateStreak(User $user): string
    {
        $today = new \DateTimeImmutable('today');
        $lastActivity = $user->getLastActivityDate();

        if ($lastActivity === null) {
            $user->setCurrentStreak(1);
            $user->setLastActivityDate($today);
            return "Première activité ! Série : 1 jour.";
        }

        $interval = $today->diff($lastActivity);
        $daysBetween = (int)$interval->format('%a');

        if ($daysBetween === 1) {
            $user->setCurrentStreak($user->getCurrentStreak() + 1);
            $user->setLastActivityDate($today);
            return "Série maintenue : " . $user->getCurrentStreak() . " jours !";
        } elseif ($daysBetween > 1) {
            $user->setCurrentStreak(1);
            $user->setLastActivityDate($today);
            return "Série réinitialisée à 1 jour.";
        }

        return ""; // Same day, no change
    }
}
