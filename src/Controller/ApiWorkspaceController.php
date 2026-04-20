<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiWorkspaceController extends AbstractController
{
    #[Route('/api/user/{id}/profile', name: 'app_api_user_profile', methods: ['GET'])]
    public function getProfile(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $profile = $user->getUserProfile();
        $avatar = $profile ? $profile->getAvatar() : null;
        
        // Use an elegant default avatar if none is set
        if (!$avatar) {
            // Using UI Avatars for dynamic initials based on user's name
            $encodedName = urlencode($user->getNom() ?: 'User');
            $avatar = "https://ui-avatars.com/api/?name={$encodedName}&background=0369a1&color=fff&size=150";
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone() ?: 'Non renseigné',
            'role' => $user->getRole(),
            'bio' => $profile && $profile->getBio() ? $profile->getBio() : 'Aucune information bio n\'a été fournie par cet utilisateur.',
            'langue' => $profile && $profile->getLangue() ? $profile->getLangue() : 'Français',
            'avatar' => $avatar
        ]);
    }

    #[Route('/api/ai/suggest-treatment', name: 'app_api_ai_suggest_treatment', methods: ['POST'])]
    public function suggestTreatment(Request $request, AiService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $diagnostic = $data['diagnostic'] ?? '';
        $observation = $data['observation'] ?? '';

        if (empty(trim($diagnostic))) {
            return new JsonResponse(['error' => 'Le diagnostic est obligatoire pour obtenir une suggestion.'], 400);
        }

        $suggestion = $aiService->suggestTreatment($diagnostic, $observation);

        return new JsonResponse(['suggestion' => $suggestion]);
    }
}
