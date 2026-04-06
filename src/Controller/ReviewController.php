<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\EvenementRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/review', name: 'app_review_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur non connecte.');
        }
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'date_desc');

        $reviews = $repo->findByFilters($user, $search, $sort, $isAdmin);
        $stats = $repo->getStats($user, $isAdmin);

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
            'stats' => $stats,
            'q' => $search,
            'sort' => $sort,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/review/new', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EvenementRepository $eventRepo): Response
    {
        $review = new Review();
        
        // Read both eventId (from user's twig edit) and event_id
        $eventId = $request->query->get('eventId') ?? $request->query->get('event_id');
        if ($eventId) {
            $event = $eventRepo->find($eventId);
            if ($event) {
                $review->setEvent($event);
            }
        }

        $includeEvent = $this->isGranted('ROLE_ADMIN') || !$review->getEvent();
        $form = $this->createForm(ReviewType::class, $review, [
            'action' => $request->getUri(), // Preserve the query string on POST
            'include_event' => $includeEvent,
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $author = $this->getUser();
            if (!$author instanceof \App\Entity\User) {
                throw $this->createAccessDeniedException('Utilisateur non connecte.');
            }
            $review->setAuthor($author);
            $em->persist($review);
            $em->flush();
            $this->addFlash('success', 'Votre avis a été enregistré.');

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_review_index');
            }

            return $this->redirectToRoute('app_evenement_index');
        }
        
        return $this->render('review/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/review/{id}', name: 'app_review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        $this->denyAccessUnlessGrantedToReview($review);

        return $this->render('review/show.html.twig', [
            'review' => $review,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/review/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGrantedToReview($review);

        $form = $this->createForm(ReviewType::class, $review, [
            'include_event' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Avis modifié avec succès.');

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_review_index');
            }

            return $this->redirectToRoute('app_evenement_index');
        }
        
        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/review/{id}', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGrantedToReview($review);

        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Requete invalide, veuillez reessayer.');

            return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_review_index' : 'app_evenement_index');
        }

        $em->remove($review);
        $em->flush();
        $this->addFlash('success', 'Avis supprime avec succes.');

        return $this->redirectToRoute($this->isGranted('ROLE_ADMIN') ? 'app_review_index' : 'app_evenement_index');
    }

    private function denyAccessUnlessGrantedToReview(Review $review): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($review->getAuthor() === null || $review->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas acceder a cet avis.');
        }
    }
}