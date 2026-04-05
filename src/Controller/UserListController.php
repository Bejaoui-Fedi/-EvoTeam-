<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class UserListController extends AbstractController
{
    #[Route('/user/list', name: 'app_user_list')]
    public function index(Request $request, UserService $userService): Response
    {
        $searchQuery = $request->query->get('q', '');
        $roleFilter = $request->query->get('role', '');
        
        if ($searchQuery || $roleFilter) {
            $users = $userService->searchUsersWithRole($searchQuery, $roleFilter);
        } else {
            $users = $userService->getAllUsers();
        }
        
        $totalUsers = count($users);
        $activeUsers = 0;
        $inactiveUsers = 0;
        
        foreach ($users as $user) {
            if ($user->isActif()) {
                $activeUsers++;
            } else {
                $inactiveUsers++;
            }
        }
        
        $activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;

        return $this->render('user_list/index.html.twig', [
            'users' => $users,
            'stats' => [
                'total' => $totalUsers,
                'active_percentage' => $activePercentage,
                'inactive' => $inactiveUsers
            ]
        ]);
    }

    #[Route('/user/toggle/{id}', name: 'app_user_toggle', methods: ['POST'])]
    public function toggle(User $user, UserService $userService, Request $request): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $user->setActif(!$user->isActif());
            $userService->updateUser($user);
        }

        return $this->redirectToRoute('app_user_list', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/user/delete/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserService $userService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userService->deleteUser($user);
        }

        return $this->redirectToRoute('app_user_list', [], Response::HTTP_SEE_OTHER);
    }
}
