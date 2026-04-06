<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Entity\User;
use App\Entity\UserProfile;
use App\Service\UserService;
use App\Service\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
=======
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
>>>>>>> exercisemanagement
use Symfony\Component\Routing\Attribute\Route;

final class UserFormController extends AbstractController
{
<<<<<<< HEAD
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

            if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'Tous les champs sont obligatoires.',
                ]);
            }

            // Validation du numéro de téléphone tunisien (exactement 8 chiffres)
            if (!preg_match('/^[0-9]{8}$/', $phone)) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'Le numéro de téléphone doit contenir exactement 8 chiffres.',
                ]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'L\'adresse email n\'est pas valide.',
                ]);
            }

            if (strlen($password) < 8) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'Le mot de passe doit faire au moins 8 caractères.',
                ]);
            }

            if ($password !== $confirmPassword) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'Les mots de passe ne correspondent pas.',
                ]);
            }

            // Check if user exists
            if ($userService->findByEmail($email)) {
                return $this->render('user_form/index.html.twig', [
                    'error' => 'Un utilisateur avec cet email existe déjà.',
                ]);
            }

            // Map role to valid DB Enum
            $role = match($accountType) {
                'coach', 'psychologue' => 'PSY_COACH',
                default => 'PATIENT',
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
            // $userProfile = new UserProfile();
            // $userProfile->setUser($user);
            // $userProfile->setDateCreation(new \DateTime());
            // $userProfile->setNotificationsEmail(true);
            // $userProfile->setNotificationsSms(false);
            // $userProfileService->createUserProfile($userProfile);

            // Redirect to login or auto-login
            return $this->redirectToRoute('app_user_login', ['success' => 'Compte créé avec succès ! Veuillez vous connecter.']);
        }

=======
    #[Route('/user/form', name: 'app_user_form')]
    public function index(): Response
    {
>>>>>>> exercisemanagement
        return $this->render('user_form/index.html.twig', [
            'controller_name' => 'UserFormController',
        ]);
    }
}
