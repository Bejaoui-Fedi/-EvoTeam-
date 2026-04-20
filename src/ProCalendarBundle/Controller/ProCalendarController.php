<?php

namespace App\ProCalendarBundle\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProCalendarController extends AbstractController
{
    #[Route('/pro/calendar/events', name: 'app_pro_calendar_events')]
    public function getEvents(AppointmentRepository $appointmentRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        $proId = $user->getId();

        $patientNames = [];
        $users = $userRepository->findAll();
        foreach ($users as $u) {
            $patientNames[$u->getId()] = $u->getNom();
        }

        $allAppointments = $appointmentRepository->findBy(['professionalId' => $proId]);
        $events = [];
        foreach ($allAppointments as $rdv) {
            if ($rdv->getStatut() === 'Congé') {
                $events[] = [
                    'id' => $rdv->getId(),
                    'title' => 'ABSENCE / CONGÉ',
                    'start' => $rdv->getDateRdv()->format('Y-m-d'),
                    'allDay' => true,
                    'color' => '#d1d5db', // Gris clair
                    'display' => 'background',
                    'className' => 'fc-event-absence',
                    'extendedProps' => ['statut' => 'Congé']
                ];
                continue;
            }

            $events[] = [
                'id' => $rdv->getId(),
                'title' => ($rdv->isUrgent() ? '⚠️ URGENT: ' : '') . ($patientNames[$rdv->getUserId()] ?? 'Patient'),
                'start' => $rdv->getDateRdv()->format('Y-m-d') . 'T' . $rdv->getHeureRdv()->format('H:i:s'),
                'color' => $rdv->isUrgent() ? '#ef4444' : '#0369a1',
                'extendedProps' => [
                    'motif' => $rdv->getMotif(),
                    'statut' => $rdv->getStatut()
                ]
            ];
        }

        return new JsonResponse($events);
    }

    /**
     * This action is intended to be called via {{ render(controller(...)) }}
     * from the main workspace template.
     */
    public function renderWidget(): Response
    {
        return $this->render('@ProCalendar/calendar_widget.html.twig');
    }

    #[Route('/pro/calendar/holidays', name: 'app_pro_calendar_holidays')]
    public function getHolidaysProxy(): Response
    {
        $url = 'https://www.officeholidays.com/ics-clean/tunisia';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36');
        // Bypass SSL verification for local dev environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($content === false || $httpCode >= 400) {
            return new Response('BEGIN:VCALENDAR\nVERSION:2.0\nEND:VCALENDAR', 200, [
                'Content-Type' => 'text/calendar'
            ]); // Fallback to empty calendar
        }
        
        return new Response($content, 200, [
            'Content-Type' => 'text/calendar',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
}
