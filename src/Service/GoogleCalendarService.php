<?php

namespace App\Service;

use App\Entity\Participation;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;

class GoogleCalendarService
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly ?string $serviceAccountPath = null,
        private readonly ?string $serviceAccountCalendarId = null
    ) {
    }

    public function createClient(): Client
    {
        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        return $client;
    }

    public function addParticipationEvent(Participation $participation, array $accessToken): void
    {
        $event = $participation->getEvent();
        if ($event === null) {
            return;
        }

        $client = $this->createClient();
        $client->setAccessToken($accessToken);

        $service = new Calendar($client);
        $calendarEvent = new Event([
            'summary' => '[EVOLIA] ' . $event->getName(),
            'description' => (string) $event->getDescription(),
            'location' => (string) $event->getLocation(),
            'start' => ['date' => $event->getStartDate()?->format('Y-m-d')],
            'end' => ['date' => $event->getEndDate()?->format('Y-m-d')],
        ]);

        $service->events->insert('primary', $calendarEvent);
    }

    public function addParticipationEventWithServiceAccount(Participation $participation): bool
    {
        $event = $participation->getEvent();
        if ($event === null || empty($this->serviceAccountPath) || empty($this->serviceAccountCalendarId) || !is_file($this->serviceAccountPath)) {
            return false;
        }

        $client = new Client();
        $client->setAuthConfig($this->serviceAccountPath);
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        $service = new Calendar($client);
        $calendarEvent = new Event([
            'summary' => '[EVOLIA] ' . $event->getName(),
            'description' => (string) $event->getDescription(),
            'location' => (string) $event->getLocation(),
            'start' => ['date' => $event->getStartDate()?->format('Y-m-d')],
            'end' => ['date' => $event->getEndDate()?->format('Y-m-d')],
        ]);

        $service->events->insert($this->serviceAccountCalendarId, $calendarEvent);

        return true;
    }
}
