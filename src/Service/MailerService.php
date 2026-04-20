<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendPasswordResetEmail(string $recipientEmail, string $resetCode): void
    {
        $email = (new Email())
            ->from('fbejaoui49@gmail.com')
            ->to($recipientEmail)
            ->subject('Réinitialisation de votre mot de passe - Evolia')
            ->html($this->renderEmailHtml($resetCode));

        $this->mailer->send($email);
    }

    private function renderEmailHtml(string $resetCode): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
            <h1 style='color: #2D3E50; text-align: center;'>Réinitialisation de mot de passe</h1>
            <p style='font-size: 16px; color: #555;'>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte Evolia.</p>
            <p style='font-size: 16px; color: #555;'>Voici votre code de validation (valable 15 minutes) :</p>
            <div style='background-color: #f4f7f6; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                <h2 style='color: #2D3E50; letter-spacing: 10px; font-size: 32px; margin: 0;'>" . $resetCode . "</h2>
            </div>
            <p style='font-size: 14px; color: #888; text-align: center;'>Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='font-size: 12px; color: #aaa; text-align: center;'>© 2026 Evolia. Tous droits réservés.</p>
        </div>
        ";
    }
}
