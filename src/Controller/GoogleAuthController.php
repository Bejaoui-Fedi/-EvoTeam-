<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;

class GoogleAuthController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/google/connect', name: 'google_connect')]
    public function connect(): Response
    {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
        $scope = 'https://www.googleapis.com/auth/calendar.events'; // Permission pour le calendrier

        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline', // Important pour avoir le Refresh Token
            'prompt' => 'consent'       // Force l'affichage du consentement pour avoir le refresh_token
        ]);

        return $this->redirect($url);
    }

    #[Route('/google/callback', name: 'google_callback')]
    public function callback(Request $request, EntityManagerInterface $em): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Accès refusé par Google.');
            return $this->redirectToRoute('app_user_gamification');
        }

        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            $user = $this->getUser();
            
            $user->setGoogleAccessToken($data['access_token']);
            if (isset($data['refresh_token'])) {
                $user->setGoogleRefreshToken($data['refresh_token']);
            }
            
            $em->flush();

            $this->addFlash('success', 'Votre Google Calendar est maintenant connecté !');
        } else {
            $this->addFlash('error', 'Erreur lors de la récupération du token Google.');
        }

        return $this->redirectToRoute('app_user_objective_list');
    }
}
