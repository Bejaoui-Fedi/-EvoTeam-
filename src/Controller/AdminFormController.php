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

final class AdminFormController extends AbstractController
{
    // C'est un code d'exemple, idéalement stocké en variable d'environnement ou en base
    private const ADMIN_AUTH_CODE = 'EVOLIA-ADMIN-2026';

    #[Route('/admin/form', name: 'app_admin_form', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        UserService $userService, 
        UserProfileService $userProfileService,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        if ($request->isMethod('POST')) {
            $authCode = $request->request->get('auth_code');
            $fullname = $request->request->get('fullname');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
                return $this->render('admin_form/index.html.twig', [
                    'error' => 'Tous les champs sont obligatoires.',
                ]);
            }

            // Validation du numéro de téléphone tunisien (exactement 8 chiffres)
            if (!preg_match('/^[0-9]{8}$/', $phone)) {
                return $this->render('admin_form/index.html.twig', [
                    'error' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.',
                ]);
            }

            // Replace miniaturized check -> check auth code
            if ($authCode !== self::ADMIN_AUTH_CODE) {
                return $this->render('admin_form/index.html.twig', [
                    'error' => 'Code d\'autorisation invalide.',
                ]);
            }

            if ($password !== $confirmPassword) {
                return $this->render('admin_form/index.html.twig', [
                    'error' => 'Les mots de passe ne correspondent pas.',
                ]);
            }

            // Check if user exists
            if ($userService->findByEmail($email)) {
                return $this->render('admin_form/index.html.twig', [
                    'error' => 'Un administrateur avec cet email existe déjà.',
                ]);
            }

            // Create Admin User
            $user = new User();
            $user->setNom($fullname);
            $user->setEmail($email);
            $user->setTelephone($phone);
            $user->setRole('ADMIN');
            $user->setActif(true);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $userService->createUser($user);

            // Create UserProfile for admin (optional but good for consistency)
            // $userProfile = new UserProfile();
            // $userProfile->setUser($user);
            // $userProfile->setDateCreation(new \DateTime());
            // $userProfile->setNotificationsEmail(true);
            // $userProfile->setNotificationsSms(false);
            // $userProfileService->createUserProfile($userProfile);

            return $this->redirectToRoute('app_admin_login', ['success' => 'Compte Administrateur créé avec succès ! Veuillez vous connecter.']);
        }

        return $this->render('admin_form/index.html.twig', [
            'controller_name' => 'AdminFormController',
        ]);
    }
}
