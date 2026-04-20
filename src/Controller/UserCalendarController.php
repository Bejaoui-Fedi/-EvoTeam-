<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/calendar')]
class UserCalendarController extends AbstractController
{
    #[Route('/', name: 'app_user_calendar')]
    public function index(): Response
    {
        return $this->render('user/calendar/index.html.twig', [
            'calendar_url' => 'https://calendar.google.com/calendar/embed?src=mnassrimayssa4%40gmail.com&ctz=Africa%2FTunis'
        ]);
    }
}
