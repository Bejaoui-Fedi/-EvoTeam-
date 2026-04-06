<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminNavBarController extends AbstractController
{
    #[Route('/admin/nav/bar', name: 'app_admin_nav_bar')]
    public function index(): Response
    {
        return $this->render('admin_nav_bar/index.html.twig', [
            'controller_name' => 'AdminNavBarController',
        ]);
    }
}
