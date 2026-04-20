<?php

namespace App\Service;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Participation;

class GoogleCalendarService
{
    private $serviceAccountPath;
    private $serviceAccountCalendarId;

    public function __construct(ParameterBagInterface $params)
    {
        $this->serviceAccountPath = $params->get('kernel.project_dir') . '/config/google_credentials.json';
        // In a real app, this might come from .env or a config param
        $this->serviceAccountCalendarId = 'evolia.wellness@gmail.com'; 
    }

    /**
     * For Exercises (from exercisemanagement branch)
     */
    public function createEvent(string $summary, string $description, \DateTimeInterface $start, \DateTimeInterface $end, string $calendarId = 'primary'): array
    {
        try {
            $client = new Client();
            $client->setAuthConfig($this->serviceAccountPath);
            $client->setScopes([Calendar::CALENDAR_EVENTS]);

            $service = new Calendar($client);
            $calendarEvent = new Event([
                'summary' => $summary,
                'description' => $description,
                'start' => ['dateTime' => $start->format(\DateTime::RFC3339)],
                'end' => ['dateTime' => $end->format(\DateTime::RFC3339)],
            ]);

            $result = $service->events->insert($calendarId === 'primary' ? $this->serviceAccountCalendarId : $calendarId, $calendarEvent);
            return ['success' => true, 'id' => $result->getId()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * For Participations (from Integration branch)
     */
    public function addParticipationEventWithServiceAccount(Participation $participation): bool
    {
        $event = $participation->getEvent();
        if ($event === null || !file_exists($this->serviceAccountPath)) {
            return false;
        }

        try {
            $client = new Client();
            $client->setAuthConfig($this->serviceAccountPath);
            $client->setScopes([Calendar::CALENDAR_EVENTS]);

            $service = new Calendar($client);
            $calendarEvent = new Event([
                'summary' => '[EVOLIA] ' . $event->getName(),
                'description' => (string) $event->getDescription(),
                'location' => (string) $event->getLocation(),
                'start' => [
                    'dateTime' => $event->getStartDate()?->format(\DateTime::RFC3339) ?? $event->getStartDate()?->format('Y-m-d\T00:00:00\Z')
                ],
                'end' => [
                    'dateTime' => $event->getEndDate()?->format(\DateTime::RFC3339) ?? $event->getEndDate()?->format('Y-m-d\T23:59:59\Z')
                ],
            ]);

            $service->events->insert($this->serviceAccountCalendarId, $calendarEvent);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
