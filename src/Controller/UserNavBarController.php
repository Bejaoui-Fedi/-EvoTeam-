<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserNavBarController extends AbstractController
{
    #[Route('/user/nav/bar', name: 'app_user_nav_bar')]
    public function index(): Response
    {
        return $this->render('user_nav_bar/index.html.twig', [
            'controller_name' => 'UserNavBarController',
        ]);
    }
}
