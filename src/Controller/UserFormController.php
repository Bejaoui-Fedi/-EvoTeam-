<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserFormController extends AbstractController
{
    #[Route('/user/form', name: 'app_user_form')]
    public function index(): Response
    {
        return $this->render('user_form/index.html.twig', [
            'controller_name' => 'UserFormController',
        ]);
    }
}
