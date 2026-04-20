<?php

namespace App\Service;

use App\Entity\EventHistory;
use App\Entity\Evenement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class HistoryLoggerService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function log(
        string $entityType,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?User $performedBy = null,
        ?Evenement $event = null
    ): void {
        $history = new EventHistory();
        $history->setEntityType($entityType);
        $history->setAction($action);
        $history->setSnapshotBefore($before);
        $history->setSnapshotAfter($after);
        $history->setPerformedBy($performedBy);
        $history->setEvent($event);
        $history->setCreatedAt(new \DateTime());

        $this->em->persist($history);
    }
}
