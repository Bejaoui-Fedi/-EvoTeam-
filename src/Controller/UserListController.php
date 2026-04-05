<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserListController extends AbstractController
{
    #[Route('/user/list', name: 'app_user_list')]
    public function index(): Response
    {
        return $this->render('user_list/index.html.twig', [
            'controller_name' => 'UserListController',
        ]);
    }
}
