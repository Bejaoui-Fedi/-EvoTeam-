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

final class UserFormController extends AbstractController
{
    #[Route('/user/form', name: 'app_user_form', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        UserService $userService, 
        UserProfileService $userProfileService,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        if ($request->isMethod('POST')) {
            $fullname = $request->request->get('fullname');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $accountType = $request->request->get('account_type'); // patient, coach, psychologue
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            $formData = [
                'fullname'     => $fullname,
                'email'        => $email,
                'phone'        => $phone,
                'account_type' => $accountType,
            ];

            if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'Tous les champs sont obligatoires.',
                ]));
            }

            // Validation du numéro de téléphone tunisien (exactement 8 chiffres)
            if (!preg_match('/^[0-9]{8}$/', $phone)) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.',
                ]));
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'L\'adresse email n\'est pas valide.',
                ]));
            }

            if (strlen($password) < 8) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'Le mot de passe doit faire au moins 8 caractères.',
                ]));
            }

            if ($password !== $confirmPassword) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'Les mots de passe ne correspondent pas.',
                ]));
            }

            // Check if user exists
            if ($userService->findByEmail($email)) {
                return $this->render('user_form/index.html.twig', array_merge($formData, [
                    'error' => 'Un utilisateur avec cet email existe déjà.',
                ]));
            }

            // Map role
            $role = match($accountType) {
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

            // Redirect to login or auto-login
            return $this->redirectToRoute('app_user_login', ['success' => 'Compte créé avec succès ! Veuillez vous connecter.']);
        }

        return $this->render('user_form/index.html.twig', [
            'controller_name' => 'UserFormController',
        ]);
    }
}
