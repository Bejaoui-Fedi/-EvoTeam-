<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminDebugController extends AbstractController
{
    #[Route('/admin/debug', name: 'app_admin_debug')]
    public function index(TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        $roles = $token ? $token->getRoleNames() : [];
        $email = $user ? $user->getUserIdentifier() : 'no user';
        
        return new Response('<pre>' . print_r([
            'email' => $email,
            'roles' => $roles,
            'db_role' => $user ? (method_exists($user, 'getRole') ? $user->getRole() : 'no getRole') : '',
        ], true) . '</pre>');
    }
}
