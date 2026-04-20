<?php

namespace App\Controller;

use App\Repository\ExerciseCompletionRepository;
use App\Service\PredictiveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/progression')]
#[IsGranted('ROLE_USER')]
class UserGamificationController extends AbstractController
{
    #[Route('/', name: 'app_user_gamification', methods: ['GET'])]
    public function index(ExerciseCompletionRepository $repo, PredictiveService $predictive): Response
    {
        $user = $this->getUser();
        
        $xp = $user->getXp();
        $nextLevelXp = 100;
        $nextLevelName = 'HABITUE';

        if ($xp >= 100 && $xp < 300) {
            $nextLevelXp = 300;
            $nextLevelName = 'ATHLETE';
        } elseif ($xp >= 300 && $xp < 600) {
            $nextLevelXp = 600;
            $nextLevelName = 'EXPERT';
        } elseif ($xp >= 600 && $xp < 1000) {
            $nextLevelXp = 1000;
            $nextLevelName = 'MAITRE';
        } elseif ($xp >= 1000) {
            $nextLevelXp = $xp;
            $nextLevelName = 'NIVEAU MAX';
        }

        $progressPercent = $nextLevelXp > 0 ? ($xp / $nextLevelXp) * 100 : 100;

        $stats = [
            'totalCompletions' => $repo->count(['user' => $user]),
            'velocity' => $predictive->calculateVelocity($user),
            'xpNextLevel' => max(0, $nextLevelXp - $xp),
            'progressPercent' => $progressPercent,
            'nextLevelName' => $nextLevelName
        ];

        $recentActivity = $repo->findBy(['user' => $user], ['completedAt' => 'DESC'], 6);

        return $this->render('user/gamification/index.html.twig', [
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ]);
    }

    #[Route('/certificate', name: 'app_user_gamification_certificate', methods: ['GET'])]
    public function certificate(): Response
    {
        $user = $this->getUser();
        
        if ($user->getXp() < 1000) {
            $this->addFlash('warning', 'Vous devez être MAITRE pour obtenir votre certificat.');
            return $this->redirectToRoute('app_user_gamification');
        }

        $html = $this->renderView('user/gamification/certificate.html.twig', [
            'user' => $user
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Certificat_Evolia_Maitre.pdf"'
            ]
        );
    }
}
