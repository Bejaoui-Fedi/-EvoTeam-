<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private SmsService $smsService;

    public function __construct(MailerInterface $mailer, Environment $twig, SmsService $smsService)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->smsService = $smsService;
    }

    /**
     * Envoie un email de confirmation au patient.
     */
    public function sendConfirmationEmail(Appointment $appointment, User $patient): void
    {
        $email = (new Email())
            ->from('molka.jbeli25@gmail.com')
            ->to($patient->getEmail())
            ->subject('Confirmation de votre rendez-vous - Evolia')
            ->html($this->twig->render('emails/appointment_confirmation.html.twig', [
                'appointment' => $appointment,
                'patient' => $patient,
            ]));

        $this->mailer->send($email);
    }

    /**
     * Envoie une alerte d'urgence au professionnel.
     */
    public function sendUrgencyAlert(Appointment $appointment, User $patient, User $professional): void
    {
        $email = (new Email())
            ->from('molka.jbeli25@gmail.com')
            ->to($professional->getEmail())
            ->subject('⚠️ ALERTE URGENCE : Nouveau rendez-vous critique')
            ->priority(Email::PRIORITY_HIGHEST)
            ->html($this->twig->render('emails/urgency_alert.html.twig', [
                'appointment' => $appointment,
                'patient' => $patient,
                'pro' => $professional,
            ]));

        $this->mailer->send($email);
    }

    /**
     * Envoie un SMS de confirmation immédiat au patient.
     */
    public function sendSmsConfirmation(Appointment $appointment, User $patient): void
    {
        if (!$patient->getTelephone()) return;

        $dateStr = $appointment->getDateRdv()->format('d/m/Y');
        $heureStr = $appointment->getHeureRdv()->format('H:i');
        $message = "Confirmé - Evolia: Votre RDV le $dateStr à $heureStr avec un spécialiste est validé. Merci de votre confiance.";

        $this->smsService->sendSms($patient->getTelephone(), $message);
    }

    /**
     * Envoie un SMS d'alerte immédiat au professionnel pour les urgences.
     */
    public function sendUrgencySms(Appointment $appointment, User $professional): void
    {
        if (!$professional->getTelephone()) return;

        $message = "⚠️ ALERTE EVOLIA : Un nouveau cas URGENT (ID #{$appointment->getId()}) a été détecté par l'IA et requiert votre attention.";

        $this->smsService->sendSms($professional->getTelephone(), $message);
    }

    /**
     * Envoie un SMS de rappel.
     */
    public function sendSmsReminder(Appointment $appointment, User $patient): void
    {
        if (!$patient->getTelephone()) return;

        $dateRdv = $appointment->getDateRdv();
        $today = new \DateTime('today');
        $tomorrow = (new \DateTime('today'))->modify('+1 day');

        $heureStr = $appointment->getHeureRdv()->format('H:i');
        
        if ($dateRdv->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            $dateStr = "DEMAIN";
        } elseif ($dateRdv->format('Y-m-d') === $today->format('Y-m-d')) {
            $dateStr = "AUJOURD'HUI";
        } else {
            $dateStr = "le " . $dateRdv->format('d/m');
        }

        $message = "Rappel Evolia: Vous avez rendez-vous $dateStr à $heureStr. À bientôt !";

        $this->smsService->sendSms($patient->getTelephone(), $message);
    }

    /**
     * Notifie le patient du changement de statut de son RDV.
     */
    public function sendStatusUpdateNotification(Appointment $appointment, User $patient): void
    {
        $status = $appointment->getStatut();
        $dateStr = $appointment->getDateRdv()->format('d/m/Y');
        $heureStr = $appointment->getHeureRdv()->format('H:i');

        // 1. SMS (Indépendant)
        if ($patient->getTelephone()) {
            try {
                $smsMessage = "Evolia Elite: Le statut de votre RDV du $dateStr à $heureStr est passé à : " . mb_strtoupper($status) . ".";
                $this->smsService->sendSms($patient->getTelephone(), $smsMessage);
            } catch (\Exception $e) {
                // On ignore l'erreur SMS pour laisser passer l'email
            }
        }

        // 2. Email (Indépendant)
        try {
            $email = (new Email())
                ->from('molka.jbeli25@gmail.com')
                ->to($patient->getEmail())
                ->subject('Mise à jour de votre rendez-vous - Evolia')
                ->html($this->twig->render('emails/status_update.html.twig', [
                    'appointment' => $appointment,
                    'patient' => $patient,
                    'status' => $status,
                ]));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error or handle it
        }
    }
    /**
     * Envoie le résumé IA de la consultation au patient.
     */
    public function sendConsultationSummary(\App\Entity\Consultation $consultation, User $patient): void
    {
        try {
            $email = (new Email())
                ->from('molka.jbeli25@gmail.com')
                ->to($patient->getEmail())
                ->subject('Résumé de votre consultation - Evolia Elite')
                ->html($this->twig->render('emails/consultation_summary.html.twig', [
                    'consultation' => $consultation,
                    'patient' => $patient,
                ]));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log silenteux pour ne pas bloquer le workflow métier
        }
    }

    /**
     * Envoie le lien de confirmation Smart Confirm (J-2).
     */
    public function sendSmartConfirmEmail(Appointment $appointment, User $patient, string $token): void
    {
        try {
            $email = (new Email())
                ->from('molka.jbeli25@gmail.com')
                ->to($patient->getEmail())
                ->subject('Veuillez confirmer votre rendez-vous - Evolia Elite')
                ->html($this->twig->render('emails/smart_confirm.html.twig', [
                    'appointment' => $appointment,
                    'patient' => $patient,
                    'token' => $token,
                ]));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log silenteux pour ne pas bloquer le script
        }
    }
}
