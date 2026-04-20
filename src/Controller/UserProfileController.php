<?php

namespace App\Controller;

use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserProfileController extends AbstractController
{
    #[Route('/user/profile', name: 'app_user_profile')]
    public function index(UserProfileService $userProfileService): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_user_login');
        }

        $userProfile = $userProfileService->findOrCreateByUserId($user);

        return $this->render('user_profile/index.html.twig', [
            'user' => $user,
            'userProfile' => $userProfile,
        ]);
    }

    #[Route('/user/profile/edit', name: 'app_user_profile_edit')]
    public function edit(UserProfileService $userProfileService): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_user_login');

        $userProfile = $userProfileService->findOrCreateByUserId($user);

        return $this->render('user_profile/edit.html.twig', [
            'user' => $user,
            'userProfile' => $userProfile,
        ]);
    }

    #[Route('/user/profile/update', name: 'app_user_profile_update', methods: ['POST'])]
    public function update(
        Request $request, 
        UserProfileService $userProfileService,
        \App\Service\UserService $userService,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_user_login');

        $userProfile = $userProfileService->findOrCreateByUserId($user);

        $nom = $request->request->get('nom');
        $phone = $request->request->get('phone');
        $bio = $request->request->get('bio');
        $habitAiToken = $request->request->get('habitAiToken');
        $avatarFile = $request->files->get('avatar');

        if (empty($nom) || empty($phone)) {
            $this->addFlash('error', 'Le nom et le téléphone sont obligatoires.');
            return $this->redirectToRoute('app_user_profile_edit');
        }

        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_user_profile_edit');
            }
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_user_profile_edit');
            }
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        // Update User info
        $user->setNom($nom);
        $user->setTelephone($phone);
        $user->setHabitAiToken($habitAiToken);
        $userService->updateUser($user);

        // Update Profile info
        $userProfile->setBio($bio);

        if ($avatarFile) {
            $newFilename = uniqid().'.'.$avatarFile->guessExtension();
            try {
                $avatarFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/avatars',
                    $newFilename
                );
                
                // Optional: Delete old avatar
                if ($userProfile->getAvatar()) {
                    $oldPath = $this->getParameter('kernel.project_dir').'/public/uploads/avatars/'.$userProfile->getAvatar();
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $userProfile->setAvatar($newFilename);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }

        $userProfileService->updateUserProfile($userProfile);

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('app_user_profile');
    }
}
