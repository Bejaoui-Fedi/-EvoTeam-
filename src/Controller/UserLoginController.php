<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
=======
>>>>>>> exercisemanagement

final class UserLoginController extends AbstractController
{
    #[Route('/user/login', name: 'app_user_login')]
<<<<<<< HEAD
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user_login/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/user/logout', name: 'app_user_logout')]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
=======
    public function index(): Response
    {
        return $this->render('user_login/index.html.twig', [
            'controller_name' => 'UserLoginController',
        ]);
    }
>>>>>>> exercisemanagement
}
