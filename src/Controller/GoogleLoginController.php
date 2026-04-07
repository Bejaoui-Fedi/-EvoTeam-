<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GoogleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use App\Entity\UserProfile;

#[Route('/google')]
final class GoogleLoginController extends AbstractController
{
    #[Route('/connect', name: 'app_google_connect')]
    public function connect(GoogleService $googleService): Response
    {
        return $this->redirect($googleService->getAuthUrl());
    }

    #[Route('/callback', name: 'app_google_callback')]
    public function callback(
        Request $request,
        GoogleService $googleService,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        FormLoginAuthenticator $formLoginAuthenticator
    ): Response {
        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('danger', 'La connexion Google a échoué.');
            return $this->redirectToRoute('app_user_login');
        }

        $googleUser = $googleService->fetchUserFromCode($code);
        if (!$googleUser || !isset($googleUser['email'])) {
            $this->addFlash('danger', 'Impossible de récupérer les informations Google.');
            return $this->redirectToRoute('app_user_login');
        }

        $email = $googleUser['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Create user if not exists
            $user = new User();
            $user->setEmail($email);
            $user->setNom($googleUser['name'] ?? explode('@', $email)[0]);
            $user->setPassword(bin2hex(random_bytes(16))); // Random password
            $user->setRole('ROLE_PATIENT');
            $user->setActif(true);
            
            $entityManager->persist($user);
        } elseif ($user->getRole() === 'ROLE_USER') {
            // If user exists but has the wrong role from previous attempts, fix it
            $user->setRole('ROLE_PATIENT');
        }

        // Ensure UserProfile exists (even for existing users)
        $userProfile = $entityManager->getRepository(UserProfile::class)->findOneBy(['user' => $user]);
        if (!$userProfile) {
            $userProfile = new UserProfile();
            $userProfile->setUser($user);
            $userProfile->setDateCreation(new \DateTime());
            $userProfile->setNotificationsEmail(true);
            $userProfile->setNotificationsSms(false);
            $entityManager->persist($userProfile);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Bienvenue ' . $user->getNom() . ' !');

        return $userAuthenticator->authenticateUser(
            $user,
            $formLoginAuthenticator,
            $request
        );
    }
}
