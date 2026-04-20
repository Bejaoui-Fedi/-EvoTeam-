<?php

namespace App\Controller;

use App\Repository\EventHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminHistoryController extends AbstractController
{
    #[Route('/admin/history', name: 'app_admin_history', methods: ['GET'])]
    public function index(Request $request, EventHistoryRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityType = $request->query->get('entityType');
        $action = $request->query->get('action');

        return $this->render('admin_history/index.html.twig', [
            'histories' => $repository->findLatest(is_string($entityType) ? $entityType : null, is_string($action) ? $action : null),
            'entityType' => $entityType,
            'action' => $action,
        ]);
    }
}
