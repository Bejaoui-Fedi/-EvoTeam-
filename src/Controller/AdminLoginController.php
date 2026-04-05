<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminLoginController extends AbstractController
{
    #[Route('/admin/login', name: 'app_admin_login')]
    public function index(): Response
    {
        return $this->render('admin_login/index.html.twig', [
            'controller_name' => 'AdminLoginController',
        ]);
    }
}
