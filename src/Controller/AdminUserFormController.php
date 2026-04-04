<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Service\UserService;
use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AdminUserFormController extends AbstractController
{
    #[Route('/admin/user/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        UserService $userService, 
        UserProfileService $userProfileService,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($request->isMethod('POST')) {
            $fullname = $request->request->get('fullname');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $accountType = $request->request->get('account_type');
            $password = $request->request->get('password');

            if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('admin_user_form/index.html.twig', ['is_edit' => false]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'L\'adresse email n\'est pas valide.');
                return $this->render('admin_user_form/index.html.twig', ['is_edit' => false]);
            }

            if (!preg_match('/^[0-9]{8}$/', $phone)) {
                $this->addFlash('error', 'Le numéro de téléphone doit contenir exactement 8 chiffres.');
                return $this->render('admin_user_form/index.html.twig', ['is_edit' => false]);
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit faire au moins 8 caractères.');
                return $this->render('admin_user_form/index.html.twig', ['is_edit' => false]);
            }

            // Check if user exists
            if ($userService->findByEmail($email)) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->render('admin_user_form/index.html.twig', ['is_edit' => false]);
            }

            // Find role
            $role = match($accountType) {
                'admin' => 'ROLE_ADMIN',
                'coach' => 'ROLE_COACH',
                'psychologue' => 'ROLE_PSYCHOLOGUE',
                default => 'ROLE_PATIENT',
            };

            // Create User
            $user = new User();
            $user->setNom($fullname);
            $user->setEmail($email);
            $user->setTelephone($phone);
            $user->setRole($role);
            $user->setActif(true);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $userService->createUser($user);

            // Create UserProfile
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
            $userProfile->setDateCreation(new \DateTime());
            $userProfile->setNotificationsEmail(true);
            $userProfile->setNotificationsSms(false);
            
            $userProfileService->createUserProfile($userProfile);

            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('admin_user_form/index.html.twig', [
            'is_edit' => false
        ]);
    }

    #[Route('/admin/user/edit/{id}', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request, 
        UserService $userService, 
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        if ($request->isMethod('POST')) {
            $fullname = $request->request->get('fullname');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $accountType = $request->request->get('account_type');
            $password = $request->request->get('password');

            if (empty($fullname) || empty($email) || empty($phone)) {
                $this->addFlash('error', 'Le nom, l\'email et le téléphone sont obligatoires.');
                return $this->render('admin_user_form/index.html.twig', ['user' => $user, 'is_edit' => true]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'L\'adresse email n\'est pas valide.');
                return $this->render('admin_user_form/index.html.twig', ['user' => $user, 'is_edit' => true]);
            }

            if (!preg_match('/^[0-9]{8}$/', $phone)) {
                $this->addFlash('error', 'Le numéro de téléphone doit contenir exactement 8 chiffres.');
                return $this->render('admin_user_form/index.html.twig', ['user' => $user, 'is_edit' => true]);
            }

            // Check if email is taken by someone else
            $existingUser = $userService->findByEmail($email);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->render('admin_user_form/index.html.twig', ['user' => $user, 'is_edit' => true]);
            }

            // Find role
            $role = match($accountType) {
                'admin' => 'ROLE_ADMIN',
                'coach' => 'ROLE_COACH',
                'psychologue' => 'ROLE_PSYCHOLOGUE',
                default => 'ROLE_PATIENT',
            };

            $user->setNom($fullname);
            $user->setEmail($email);
            $user->setTelephone($phone);
            $user->setRole($role);

            // Only update password if provided
            if (!empty($password)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            $userService->updateUser($user);

            $this->addFlash('success', 'Utilisateur mis à jour avec succès !');
            return $this->redirectToRoute('app_user_list');
        }

        return $this->render('admin_user_form/index.html.twig', [
            'user' => $user,
            'is_edit' => true
        ]);
    }
}
