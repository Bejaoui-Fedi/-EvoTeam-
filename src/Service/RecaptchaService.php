<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private string $secretKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $secretKey
    ) {
        $this->secretKey = $secretKey;
    }

    public function verify(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                ],
            ]);

            $data = $response->toArray();
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
