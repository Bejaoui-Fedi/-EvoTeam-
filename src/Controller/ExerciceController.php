<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exercice')]
final class ExerciceController extends AbstractController
{
    // ──────────────────────────────────────────
    //  LISTE
    // ──────────────────────────────────────────
    #[Route('/', name: 'exercice_index', methods: ['GET'])]
    public function index(Request $request, ExerciceRepository $repository): Response
    {
        // 1. Récupération des paramètres de filtrage
        $search     = $request->query->get('q', null);
        $difficulte = $request->query->get('difficulte', null);
        $sort       = $request->query->get('sort', 'date');
        $direction  = $request->query->get('direction', 'DESC');

        // 2. Requête multicritère
        $exercices = $repository->findByCriteria($search, $difficulte, $sort, $direction);

        // 3. Calculs des Statistiques
        $total         = count($exercices);
        $dureeTotale   = array_reduce($exercices, fn($sum, $e) => $sum + $e->getDuree(), 0);
        $dureeMoyenne  = $total > 0 ? round($dureeTotale / $total) : 0;
        $nbDifficiles  = count(array_filter($exercices, fn($e) => $e->getDifficulte() === 'difficile'));

        return $this->render('exercice/index.html.twig', [
            'exercices'  => $exercices,
            'search'     => $search,
            'difficulte' => $difficulte,
            'sort'       => $sort,
            'direction'  => $direction,
            'stats'      => [
                'total'        => $total,
                'dureeMoyenne' => $dureeMoyenne,
                'difficiles'   => $nbDifficiles
            ]
        ]);
    }

    // ──────────────────────────────────────────
    //  AJOUT
    // ──────────────────────────────────────────
    #[Route('/new', name: 'exercice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, \App\Repository\ObjectifRepository $objectifRepository): Response
    {
        $exercice = new Exercice();
        
        // Pré-sélectionner l'objectif si l'id est passé en paramètre
        $objectifId = $request->query->get('objectif_id');
        if ($objectifId) {
            $objectif = $objectifRepository->find($objectifId);
            if ($objectif) {
                $exercice->setObjectif($objectif);
            }
        }

        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exercice);
            $em->flush();
            $this->addFlash('success', 'Exercice créé avec succès !');

            // Rediriger vers l'objectif si présent
            if ($exercice->getObjectif()) {
                return $this->redirectToRoute('objectif_show', ['id' => $exercice->getObjectif()->getId()]);
            }

            return $this->redirectToRoute('exercice_index');
        }

        return $this->render('exercice/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ──────────────────────────────────────────
    //  DÉTAIL
    // ──────────────────────────────────────────
    #[Route('/{id}', name: 'exercice_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('exercice/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    // ──────────────────────────────────────────
    //  MODIFICATION
    // ──────────────────────────────────────────
    #[Route('/{id}/edit', name: 'exercice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exercice $exercice, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ExerciceType::class, $exercice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $exercice->setUpdatedAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Exercice modifié avec succès !');
            return $this->redirectToRoute('exercice_show', ['id' => $exercice->getId()]);
        }

        return $this->render('exercice/edit.html.twig', [
            'exercice' => $exercice,
            'form'     => $form->createView(),
        ]);
    }

    // ──────────────────────────────────────────
    //  SUPPRESSION
    // ──────────────────────────────────────────
    #[Route('/{id}/delete', name: 'exercice_delete', methods: ['POST'])]
    public function delete(Request $request, Exercice $exercice, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $exercice->getId(), $request->request->get('_token'))) {
            $em->remove($exercice);
            $em->flush();
            $this->addFlash('success', 'Exercice supprimé avec succès !');
        }

        return $this->redirectToRoute('exercice_index');
    }

    // ──────────────────────────────────────────
    //  EXPORT PDF
    // ──────────────────────────────────────────
    #[Route('/export/pdf', name: 'exercice_export_pdf', methods: ['GET'])]
    public function exportPdf(ExerciceRepository $repository): Response
    {
        $exercices = $repository->findByCriteria(null, null, 'date', 'DESC');

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('exercice/pdf_report.html.twig', [
            'exercices' => $exercices,
            'date'      => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Rapport_Exercices_Evolia.pdf"',
        ]);
    }
}
