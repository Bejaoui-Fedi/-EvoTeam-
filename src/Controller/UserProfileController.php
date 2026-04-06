<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
=======
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
>>>>>>> exercisemanagement
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserProfileController extends AbstractController
{
    #[Route('/user/profile', name: 'app_user_profile')]
<<<<<<< HEAD
    public function index(UserProfileService $userProfileService): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_user_login');
        }

        $userProfile = $userProfileService->findByUserId($user->getId());

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

        $userProfile = $userProfileService->findByUserId($user->getId());

        return $this->render('user_profile/edit.html.twig', [
            'user' => $user,
            'userProfile' => $userProfile,
        ]);
    }

    #[Route('/user/profile/update', name: 'app_user_profile_update', methods: ['POST'])]
    public function update(
        Request $request, 
        UserProfileService $userProfileService,
        \App\Service\UserService $userService
    ): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_user_login');

        $userProfile = $userProfileService->findByUserId($user->getId());

        $nom = $request->request->get('nom');
        $phone = $request->request->get('phone');
        $bio = $request->request->get('bio');
        $avatarFile = $request->files->get('avatar');

        if (empty($nom) || empty($phone)) {
            $this->addFlash('error', 'Le nom et le téléphone sont obligatoires.');
            return $this->redirectToRoute('app_user_profile_edit');
        }

        // Update User info
        $user->setNom($nom);
        $user->setTelephone($phone);
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
=======
    public function index(): Response
    {
        return $this->render('user_profile/index.html.twig', [
            'controller_name' => 'UserProfileController',
        ]);
    }
>>>>>>> exercisemanagement
}
