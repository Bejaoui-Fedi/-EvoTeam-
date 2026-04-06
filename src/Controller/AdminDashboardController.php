<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
<<<<<<< HEAD
    public function index(\App\Repository\UserRepository $userRepository): Response
    {
        $userCount = $userRepository->count([]);

        return $this->render('admin_dashboard/index.html.twig', [
            'userCount' => $userCount,
=======
    public function index(): Response
    {
        return $this->render('admin_dashboard/index.html.twig', [
            'controller_name' => 'AdminDashboardController',
>>>>>>> exercisemanagement
        ]);
    }
}
