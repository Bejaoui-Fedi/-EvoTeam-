<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserDashboardController extends AbstractController
{
    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function index(): Response
    {
        // Mock data for innovative display
        $quote = [
            'text' => "Le bien-être n'est pas une destination, c'est un voyage quotidien vers l'équilibre.",
            'author' => "Anonyme",
            'category' => "Mindset"
        ];

        $quotas = [
            ['name' => 'Sommeil', 'current' => 7, 'target' => 8, 'unit' => 'h', 'color' => '#6366f1'],
            ['name' => 'Hydratation', 'current' => 1.5, 'target' => 2.5, 'unit' => 'L', 'color' => '#3b82f6'],
            ['name' => 'Focus', 'current' => 45, 'target' => 60, 'unit' => 'min', 'color' => '#f59e0b']
        ];

        $studies = [
            [
                'title' => 'Neuroplasticité & Focus',
                'summary' => 'Comment 15 min de concentration profonde remodèle votre cerveau.',
                'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=400',
                'tag' => 'Focus'
            ],
            [
                'title' => 'Sommeil & Rétablissement',
                'summary' => 'Les bienfaits du cycle REM sur la gestion émotionnelle.',
                'image' => 'https://images.unsplash.com/photo-1470252649378-9c29740c9fa8?auto=format&fit=crop&q=80&w=400',
                'tag' => 'Sommeil'
            ]
        ];

        $videos = [
            ['id' => 'inpok4MKVLM', 'title' => 'Morning Routine', 'duration' => '12m'],
            ['id' => '1_pUp477zYo', 'title' => 'Deep Sleep Music', 'duration' => '8m']
        ];

        $stats = [
            'streak' => 5,
            'completion' => 85,
            'happiness' => 'Élevé'
        ];

        return $this->render('user_dashboard/index.html.twig', [
            'controller_name' => 'UserDashboardController',
            'quote' => $quote,
            'quotas' => $quotas,
            'studies' => $studies,
            'videos' => $videos,
            'stats' => $stats,
        ]);
    }
}
