<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleCalendarService
{
    private $httpClient;
    private $params;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
    }

    public function createEvent(string $summary, string $description, \DateTimeInterface $start, \DateTimeInterface $end, string $calendarId = 'primary'): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['success' => false, 'message' => 'Erreur d\'authentification Google (Vérifiez le fichier credentials.json).'];

        try {
            $response = $this->httpClient->request('POST', "https://www.googleapis.com/calendar/v3/calendars/$calendarId/events", [
                'auth_bearer' => $accessToken,
                'json' => [
                    'summary' => $summary,
                    'description' => $description,
                    'start' => ['dateTime' => $start->format(\DateTime::RFC3339)],
                    'end' => ['dateTime' => $end->format(\DateTime::RFC3339)],
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return ['success' => true, 'id' => $data['id']];
            }

            if ($response->getStatusCode() === 403 || $response->getStatusCode() === 404) {
                return ['success' => false, 'message' => 'Accès refusé au calendrier. Avez-vous partagé votre calendrier avec le compte de service Google ?'];
            }

            return ['success' => false, 'message' => 'Erreur Google : ' . $response->getStatusCode()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getAccessToken(): ?string
    {
        $credsPath = $this->params->get('kernel.project_dir') . '/config/google_credentials.json';
        if (!file_exists($credsPath)) return null;

        $creds = json_decode(file_get_contents($credsPath), true);
        $clientEmail = $creds['client_email'];
        $privateKey = $creds['private_key'];

        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = base64_encode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        $signatureSource = "$header.$payload";
        
        $res = openssl_get_privatekey($privateKey);
        if (!$res || !openssl_sign($signatureSource, $signature, $res, 'SHA256')) {
            return null;
        }
        
        $signature = base64_encode($signature);
        $jwt = "$header.$payload.$signature";

        try {
            $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['access_token'];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
