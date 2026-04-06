<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
=======
>>>>>>> exercisemanagement

final class AdminLoginController extends AbstractController
{
    #[Route('/admin/login', name: 'app_admin_login')]
<<<<<<< HEAD
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin_login/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'app_admin_logout')]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
=======
    public function index(): Response
    {
        return $this->render('admin_login/index.html.twig', [
            'controller_name' => 'AdminLoginController',
        ]);
    }
>>>>>>> exercisemanagement
}
