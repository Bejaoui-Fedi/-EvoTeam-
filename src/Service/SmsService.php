<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct(string $twilioSid, string $twilioToken, string $twilioPhone)
    {
        $this->sid = $twilioSid;
        $this->token = $twilioToken;
        $this->from = $twilioPhone;
    }

    /**
     * Envoie un SMS à un destinataire donné.
     * Le numéro doit être au format international (ex: +216...).
     */
    public function sendSms(string $to, string $message): string
    {
        $to = $this->formatPhoneNumber($to);
        $client = new Client($this->sid, $this->token);
        $response = $client->messages->create(
            $to,
            [
                'from' => $this->from,
                'body' => $message
            ]
        );

        return "✅ SMS envoyé avec succès ! SID: " . $response->sid;
    }

    /**
     * Formate le numéro de téléphone pour Twilio (E.164).
     */
    private function formatPhoneNumber(string $number): string
    {
        // Nettoyage : garder uniquement les chiffres
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Si c'est un numéro tunisien à 8 chiffres, on ajoute +216
        if (strlen($cleaned) === 8) {
            return '+216' . $cleaned;
        }

        // Si le numéro commence déjà par 216 mais n'a pas le +
        if (str_starts_with($cleaned, '216') && strlen($cleaned) === 11) {
            return '+' . $cleaned;
        }

        // Par défaut, si le "+" manque on essaie de le rajouter intelligemment
        if (!str_starts_with($number, '+')) {
            return '+' . $cleaned;
        }

        return $number;
    }
}
