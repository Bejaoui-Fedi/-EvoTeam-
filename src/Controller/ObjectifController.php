<?php

namespace App\Controller;

use App\Entity\Objectif;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/objectif')]
final class ObjectifController extends AbstractController
{
    // ──────────────────────────────────────────
    //  LISTE
    // ──────────────────────────────────────────
    #[Route('/', name: 'objectif_index', methods: ['GET'])]
    public function index(Request $request, ObjectifRepository $repository): Response
    {
        // 1. Récupération des paramètres (avec valeurs par défaut)
        $search    = $request->query->get('q', null);
        $statut    = $request->query->get('statut', null);
        $sort      = $request->query->get('sort', 'dateDebut');
        $direction = $request->query->get('direction', 'DESC');

        // 2. Recherche multicritère via notre nouveau Repository
        $objectifs = $repository->findByCriteria($search, $statut, $sort, $direction);

        // 3. Calcul de Statistiques Avancées
        $total         = count($objectifs);
        $enCours       = count(array_filter($objectifs, fn($o) => $o->getStatut() === 'en_cours'));
        $termines      = count(array_filter($objectifs, fn($o) => $o->getStatut() === 'termine'));
        $tauxReussite  = $total > 0 ? round(($termines / $total) * 100) : 0;

        return $this->render('objectif/index.html.twig', [
            'objectifs'     => $objectifs,
            'search'        => $search,
            'statut'        => $statut,
            'sort'          => $sort,
            'direction'     => $direction,
            'stats'         => [
                'total'    => $total,
                'enCours'  => $enCours,
                'termines' => $termines,
                'taux'     => $tauxReussite
            ]
        ]);
    }

    // ──────────────────────────────────────────
    //  AJOUT
    // ──────────────────────────────────────────
    #[Route('/new', name: 'objectif_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $objectif = new Objectif();
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($objectif);
            $em->flush();
            $this->addFlash('success', 'Objectif créé avec succès !');
            return $this->redirectToRoute('objectif_index');
        }

        return $this->render('objectif/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ──────────────────────────────────────────
    //  DÉTAIL
    // ──────────────────────────────────────────
    #[Route('/{id}', name: 'objectif_show', methods: ['GET'])]
    public function show(Objectif $objectif): Response
    {
        return $this->render('objectif/show.html.twig', [
            'objectif' => $objectif,
        ]);
    }

    // ──────────────────────────────────────────
    //  MODIFICATION
    // ──────────────────────────────────────────
    #[Route('/{id}/edit', name: 'objectif_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Objectif $objectif, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Objectif modifié avec succès !');
            return $this->redirectToRoute('objectif_show', ['id' => $objectif->getId()]);
        }

        return $this->render('objectif/edit.html.twig', [
            'objectif' => $objectif,
            'form'     => $form->createView(),
        ]);
    }

    // ──────────────────────────────────────────
    //  SUPPRESSION
    // ──────────────────────────────────────────
    #[Route('/{id}/delete', name: 'objectif_delete', methods: ['POST'])]
    public function delete(Request $request, Objectif $objectif, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $objectif->getId(), $request->request->get('_token'))) {
            $em->remove($objectif);
            $em->flush();
            $this->addFlash('success', 'Objectif supprimé avec succès !');
        }

        return $this->redirectToRoute('objectif_index');
    }

    // ──────────────────────────────────────────
    //  EXPORT PDF
    // ──────────────────────────────────────────
    #[Route('/export/pdf', name: 'objectif_export_pdf', methods: ['GET'])]
    public function exportPdf(ObjectifRepository $repository): Response
    {
        // 1. Récupération des données
        $objectifs = $repository->findByCriteria(null, null, 'dateDebut', 'DESC');

        // 2. Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        // 3. Instanciation de Dompdf
        $dompdf = new Dompdf($pdfOptions);

        // 4. On génère le HTML à partir d'un template spécifique (ou existant)
        $html = $this->renderView('objectif/pdf_report.html.twig', [
            'objectifs' => $objectifs,
            'date'      => new \DateTime()
        ]);

        // 5. Chargement du HTML
        $dompdf->loadHtml($html);

        // 6. Rendu PDF (Landscape ou Portrait)
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 7. Retour du PDF au navigateur
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Rapport_Objectifs_Evolia.pdf"'
        ]);
    }
}
