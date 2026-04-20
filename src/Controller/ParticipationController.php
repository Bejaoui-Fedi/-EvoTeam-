<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Entity\User;
use App\Form\ParticipationType;
use App\Repository\ParticipationRepository;
use App\Service\GoogleCalendarService;
use App\Service\HistoryLoggerService;
use App\Service\ParticipationQrService;
use App\Service\StripePaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ParticipationController extends AbstractController
{
    #[Route('/participation/new/{id}', name: 'app_participation_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        Evenement $event,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $em,
        HistoryLoggerService $historyLogger,
        StripePaymentService $stripePaymentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }

        $existing = $participationRepository->findOneByUserAndEvent($user, (int) $event->getId());
        if ($existing !== null) {
            $this->addFlash('error', 'Vous participez deja a cet evenement.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $participation = (new Participation())
            ->setUser($user)
            ->setEvent($event)
            ->setStatus('registered')
            ->setCreatedAt(new \DateTime());

        $form = $this->createForm(ParticipationType::class, $participation, ['include_event' => false]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('participation/new.html.twig', [
                'form' => $form->createView(),
                'event' => $event,
                'isAdmin' => false,
            ]);
        }

        if ($participation->getPaymentMethod() === 'online') {
            $participation->setPaymentStatus('pending');
        }

        $em->persist($participation);
        $historyLogger->log('participation', 'register', null, [
            'event' => $event->getName(),
            'user' => $user->getEmail(),
            'status' => $participation->getStatus(),
            'payment' => $participation->getPaymentMethod(),
        ], $user, $event);

        $em->flush();

        if ($participation->getPaymentMethod() === 'online') {
            try {
                $checkoutUrl = $stripePaymentService->createCheckoutSession($participation);
                return $this->redirect($checkoutUrl);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur de paiement en ligne: ' . $e->getMessage());
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        $this->addFlash('success', 'Participation enregistree avec succes.');

        return $this->redirectToRoute('app_evenement_index');
    }

    #[Route('/participation/{id}/payment/success', name: 'app_participation_payment_success', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function paymentSuccess(Participation $participation, EntityManagerInterface $em): Response
    {
        if ($participation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $participation->setPaymentStatus('paid');
        $em->flush();

        $this->addFlash('success', 'Paiement reussi ! Votre participation est bien confirmee.');
        return $this->redirectToRoute('app_participation_show', ['id' => $participation->getId()]);
    }

    #[Route('/participation/{id}/payment/cancel', name: 'app_participation_payment_cancel', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function paymentCancel(Participation $participation): Response
    {
        if ($participation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->addFlash('error', 'Le paiement a ete annule. Votre participation est en attente.');
        return $this->redirectToRoute('app_participation_show', ['id' => $participation->getId()]);
    }

    #[Route('/participation/{id}/cancel', name: 'app_participation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(
        Request $request,
        Participation $participation,
        EntityManagerInterface $em,
        HistoryLoggerService $historyLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }
        $this->assertParticipationAccess($participation, $user);
        if (!$this->isCsrfTokenValid('cancel_participation_' . $participation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Requete invalide.');
            return $this->redirectToRoute('app_participation_my');
        }

        $participation->setStatus('cancelled');
        $participation->setUpdatedAt(new \DateTime());

        $historyLogger->log('participation', 'cancel', ['status' => 'registered'], ['status' => 'cancelled'], $user, $participation->getEvent());

        $em->flush();
        $this->addFlash('success', 'Participation annulee.');

        return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
    }

    #[Route('/participation/{id}', name: 'app_participation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ParticipationRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }

        $participation = $repository->find($id);
        if (!$participation instanceof Participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
        }
        $this->assertParticipationAccess($participation, $user);

        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/participation/show/{id}', name: 'app_participation_show_safe', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showSafe(int $id, ParticipationRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }

        $participation = $repository->find($id);
        if ($participation === null) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
        }

        $this->assertParticipationAccess($participation, $user);

        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/participation/{id}/edit', name: 'app_participation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Participation $participation,
        EntityManagerInterface $em,
        HistoryLoggerService $historyLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }
        $this->assertParticipationAccess($participation, $user);

        $before = [
            'status' => $participation->getStatus(),
            'event' => $participation->getEvent()?->getId(),
        ];
        $form = $this->createForm(ParticipationType::class, $participation, [
            'include_event' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participation->setUpdatedAt(new \DateTime());
            $historyLogger->log('participation', 'update', $before, [
                'status' => $participation->getStatus(),
                'event' => $participation->getEvent()?->getId(),
            ], $user, $participation->getEvent());
            $em->flush();
            $this->addFlash('success', 'Participation modifiee.');

            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
        }

        return $this->render('participation/edit.html.twig', [
            'form' => $form->createView(),
            'participation' => $participation,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/participation/{id}/delete', name: 'app_participation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Participation $participation,
        EntityManagerInterface $em,
        HistoryLoggerService $historyLogger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }
        $this->assertParticipationAccess($participation, $user);
        if (!$this->isCsrfTokenValid('delete_participation_' . $participation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Requete invalide.');
            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
        }

        $historyLogger->log('participation', 'delete', [
            'event' => $participation->getEvent()?->getName(),
            'user' => $participation->getUser()?->getEmail(),
            'status' => $participation->getStatus(),
        ], null, $user, $participation->getEvent());
        $em->remove($participation);
        $em->flush();
        $this->addFlash('success', 'Participation supprimee.');

        return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_participation_index' : 'app_participation_my');
    }

    #[Route('/participation/my', name: 'app_participation_my', methods: ['GET'])]
    public function myList(ParticipationRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Veuillez vous connecter.');
        }

        return $this->render('participation/index.html.twig', [
            'participations' => $repository->findForListing($user, false),
            'isAdmin' => false,
        ]);
    }

    #[Route('/admin/participation', name: 'app_participation_index', methods: ['GET'])]
    public function allList(ParticipationRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('participation/index.html.twig', [
            'participations' => $repository->findForListing(null, true),
            'isAdmin' => true,
        ]);
    }

    #[Route('/participation/{id}/qr', name: 'app_participation_qr', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function qr(Participation $participation, ParticipationQrService $qrService, #[Autowire('%kernel.secret%')] string $kernelSecret): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof User || $participation->getUser() !== $user)) {
            throw $this->createAccessDeniedException('Acces refuse.');
        }

        $eventId = (int) $participation->getEvent()?->getId();
        $token = $this->buildSignedToken($eventId, $kernelSecret);
        $svg = $qrService->generateEventQrSvg($token);

        return new Response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    #[Route('/participation/{id}/calendar/add', name: 'app_participation_calendar_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addToCalendar(
        Request $request,
        Participation $participation,
        GoogleCalendarService $calendarService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || $participation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Acces refuse.');
        }
        if (!$this->isCsrfTokenValid('calendar_participation_' . $participation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Requete invalide.');
            return $this->redirectToRoute('app_participation_my');
        }

        $token = $request->getSession()->get('google_access_token');
        if (!is_array($token)) {
            if ($calendarService->addParticipationEventWithServiceAccount($participation)) {
                $this->addFlash('success', 'Evenement ajoute via service account Google Calendar.');
                return $this->redirectToRoute('app_participation_my');
            }

            return $this->redirectToRoute('app_calendar_connect', ['participation' => $participation->getId()]);
        }

        $calendarService->addParticipationEvent($participation, $token);
        $this->addFlash('success', 'Evenement ajoute a votre Google Calendar.');

        return $this->redirectToRoute('app_participation_my');
    }

    private function buildSignedToken(int $id, string $secret): string
    {
        $rawId = (string) $id;
        $signature = hash_hmac('sha256', $rawId, $secret);

        return $rawId . '.' . $signature;
    }

    private function assertParticipationAccess(Participation $participation, User $user): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($participation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Acces refuse.');
        }
    }
}
