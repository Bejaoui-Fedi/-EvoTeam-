<?php

namespace App\Entity;

use App\Repository\EventHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventHistoryRepository::class)]
class EventHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $entityType = 'event';

    #[ORM\Column(length: 30)]
    private string $action = 'create';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $snapshotBefore = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $snapshotAfter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Evenement $event = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getSnapshotBefore(): ?array
    {
        return $this->snapshotBefore;
    }

    public function setSnapshotBefore(?array $snapshotBefore): static
    {
        $this->snapshotBefore = $snapshotBefore;

        return $this;
    }

    public function getSnapshotAfter(): ?array
    {
        return $this->snapshotAfter;
    }

    public function setSnapshotAfter(?array $snapshotAfter): static
    {
        $this->snapshotAfter = $snapshotAfter;

        return $this;
    }

    public function getPerformedBy(): ?User
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?User $performedBy): static
    {
        $this->performedBy = $performedBy;

        return $this;
    }

    public function getEvent(): ?Evenement
    {
        return $this->event;
    }

    public function setEvent(?Evenement $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
