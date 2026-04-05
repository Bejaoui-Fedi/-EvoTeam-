<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminFormController extends AbstractController
{
    #[Route('/admin/form', name: 'app_admin_form')]
    public function index(): Response
    {
        return $this->render('admin_form/index.html.twig', [
            'controller_name' => 'AdminFormController',
        ]);
    }
}
