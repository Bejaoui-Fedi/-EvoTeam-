<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Service\GeminiAiService;
use App\Service\HistoryLoggerService;
use App\Service\ParticipationExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvenementController extends AbstractController
{
    #[Route('/evenement', name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $repo): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'date_desc');
        $evenements = $repo->findByFilters($search, $sort);
        $stats = $repo->getStats();

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'stats' => $stats,
            'q' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/evenement/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, HistoryLoggerService $historyLogger): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($evenement);
            $user = $this->getUser();
            $historyLogger->log('event', 'create', null, [
                'name' => $evenement->getName(),
                'startDate' => $evenement->getStartDate()?->format('Y-m-d'),
                'endDate' => $evenement->getEndDate()?->format('Y-m-d'),
            ], $user instanceof User ? $user : null, $evenement);
            $em->flush();
            return $this->redirectToRoute('app_evenement_index');
        }
        
        return $this->render('evenement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/evenement/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, HistoryLoggerService $historyLogger): Response
    {
        $before = [
            'name' => $evenement->getName(),
            'startDate' => $evenement->getStartDate()?->format('Y-m-d'),
            'endDate' => $evenement->getEndDate()?->format('Y-m-d'),
            'location' => $evenement->getLocation(),
        ];
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $historyLogger->log('event', 'update', $before, [
                'name' => $evenement->getName(),
                'startDate' => $evenement->getStartDate()?->format('Y-m-d'),
                'endDate' => $evenement->getEndDate()?->format('Y-m-d'),
                'location' => $evenement->getLocation(),
            ], $user instanceof User ? $user : null, $evenement);
            $em->flush();
            return $this->redirectToRoute('app_evenement_index');
        }
        
        return $this->render('evenement/edit.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Evenement $evenement, EntityManagerInterface $em, HistoryLoggerService $historyLogger): Response
    {
        $user = $this->getUser();
        $historyLogger->log('event', 'delete', [
            'name' => $evenement->getName(),
            'startDate' => $evenement->getStartDate()?->format('Y-m-d'),
            'endDate' => $evenement->getEndDate()?->format('Y-m-d'),
            'location' => $evenement->getLocation(),
        ], null, $user instanceof User ? $user : null, $evenement);
        $em->remove($evenement);
        $em->flush();
        return $this->redirectToRoute('app_evenement_index');
    }

    #[Route('/admin/evenement/{id}/participants/export', name: 'app_evenement_participants_export', methods: ['GET'])]
    public function exportParticipants(
        Evenement $evenement,
        ParticipationRepository $participationRepository,
        ParticipationExportService $exportService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $participations = $participationRepository->findBy(['event' => $evenement], ['createdAt' => 'DESC']);
        $filename = sprintf('participants_evenement_%d.xlsx', $evenement->getId());

        return $exportService->createParticipantsXlsxResponse($participations, $filename);
    }

    #[Route('/evenement/public/{token}', name: 'app_evenement_public', methods: ['GET'])]
    public function publicFromQr(string $token, EvenementRepository $repo, #[Autowire('%kernel.secret%')] string $kernelSecret): Response
    {
        [$id, $signature] = array_pad(explode('.', $token, 2), 2, null);
        if ($id === null || $signature === null || !hash_equals(hash_hmac('sha256', $id, $kernelSecret), $signature)) {
            throw $this->createNotFoundException('Lien QR invalide.');
        }

        $event = $repo->find((int) $id);
        if (!$event instanceof Evenement) {
            throw $this->createNotFoundException('Evenement introuvable.');
        }

        return $this->render('evenement/public.html.twig', [
            'evenement' => $event,
        ]);
    }

    #[Route('/admin/evenement/generate-description', name: 'app_evenement_generate_desc', methods: ['POST'])]
    public function generateDescription(Request $request, GeminiAiService $aiService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? '';
        $location = $data['location'] ?? '';

        if (empty($name)) {
            return new JsonResponse(['description' => 'Veuillez au moins renseigner un titre pour générer une description.'], 400);
        }

        $promptTitle = $name;
        if (!empty($location)) {
            $promptTitle .= ' à ' . $location;
        }

        $description = $aiService->generateEventDescription($promptTitle);

        return new JsonResponse(['description' => $description]);
    }
}