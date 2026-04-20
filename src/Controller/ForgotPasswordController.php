<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forgot-password')]
final class ForgotPasswordController extends AbstractController
{
    #[Route('/request', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerService $mailerService): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate a 6-digit code
                $resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->setResetToken($resetCode);
                
                // Set expiry to 15 minutes from now
                $user->setTokenExpiry(new \DateTime('+15 minutes'));
                
                $entityManager->flush();

                $mailerService->sendPasswordResetEmail($user->getEmail(), $resetCode);

                $this->addFlash('success', 'Un code de réinitialisation a été envoyé à votre adresse email.');
                return $this->redirectToRoute('app_forgot_password_reset', ['email' => $email]);
            }

            $this->addFlash('danger', 'Aucun utilisateur trouvé avec cette adresse email.');
        }

        return $this->render('forgot_password/request.html.twig');
    }

    #[Route('/reset', name: 'app_forgot_password_reset', methods: ['GET', 'POST'])]
    public function reset(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $email = $request->query->get('email') ?? $request->request->get('email');

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $csrfToken = $request->request->get('_csrf_token');

            if (!$this->isCsrfTokenValid('reset_password', $csrfToken)) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->render('forgot_password/reset.html.twig', ['email' => $email]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->render('forgot_password/reset.html.twig', ['email' => $email]);
            }

            $user = $userRepository->findOneBy(['email' => $email, 'resetToken' => $code]);

            if (!$user) {
                $this->addFlash('danger', 'Code ou email invalide.');
                return $this->render('forgot_password/reset.html.twig', ['email' => $email]);
            }

            if ($user->getTokenExpiry() < new \DateTime()) {
                $this->addFlash('danger', 'Le code a expiré.');
                return $this->render('forgot_password/reset.html.twig', ['email' => $email]);
            }

            // Change password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            // Clear token
            $user->setResetToken(null);
            $user->setTokenExpiry(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_user_login');
        }

        return $this->render('forgot_password/reset.html.twig', ['email' => $email]);
    }
}
