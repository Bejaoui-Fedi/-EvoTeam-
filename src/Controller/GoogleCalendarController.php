<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleCalendarController extends AbstractController
{
    #[Route('/calendar/connect', name: 'app_calendar_connect', methods: ['GET'])]
    public function connect(Request $request, GoogleCalendarService $calendarService): Response
    {
        $client = $calendarService->createClient();
        $participationId = $request->query->get('participation');
        if ($participationId !== null) {
            $request->getSession()->set('calendar_participation_id', (int) $participationId);
        }

        return $this->redirect($client->createAuthUrl());
    }

    #[Route('/calendar/callback', name: 'app_calendar_callback', methods: ['GET'])]
    #[Route('/google/callback', methods: ['GET'])]
    public function callback(Request $request, GoogleCalendarService $calendarService): Response
    {
        $code = $request->query->get('code');
        if (!is_string($code) || $code === '') {
            $this->addFlash('error', 'Autorisation Google invalide.');
            return $this->redirectToRoute('app_participation_my');
        }

        $client = $calendarService->createClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (!is_array($token) || isset($token['error'])) {
            $this->addFlash('error', 'Impossible de se connecter a Google Calendar.');
            return $this->redirectToRoute('app_participation_my');
        }

        $request->getSession()->set('google_access_token', $token);
        $this->addFlash('success', 'Compte Google connecte avec succes.');

        $participationId = $request->getSession()->get('calendar_participation_id');
        if (is_int($participationId)) {
            $request->getSession()->remove('calendar_participation_id');
            return $this->redirectToRoute('app_participation_my');
        }

        return $this->redirectToRoute('app_participation_my');
    }
}
