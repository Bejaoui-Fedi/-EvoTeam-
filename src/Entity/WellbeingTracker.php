<?php

namespace App\Entity;

use App\Repository\WellbeingTrackerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WellbeingTrackerRepository::class)]
class WellbeingTracker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\NotFound(action: 'ignore')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: DailyRoutineTask::class)]
    #[ORM\JoinColumn(name: 'daily_routine_task_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\NotFound(action: 'ignore')]
    private ?DailyRoutineTask $routineTask = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?int $mood = null;

    #[ORM\Column]
    private ?int $stress = null;

    #[ORM\Column]
    private ?int $energy = null;

    #[ORM\Column]
    private ?float $sleepHours = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRoutineTask(): ?DailyRoutineTask
    {
        return $this->routineTask;
    }

    public function setRoutineTask(?DailyRoutineTask $routineTask): static
    {
        $this->routineTask = $routineTask;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getMood(): ?int
    {
        return $this->mood;
    }

    public function setMood(int $mood): static
    {
        $this->mood = $mood;
        return $this;
    }

    public function getStress(): ?int
    {
        return $this->stress;
    }

    public function setStress(int $stress): static
    {
        $this->stress = $stress;
        return $this;
    }

    public function getEnergy(): ?int
    {
        return $this->energy;
    }

    public function setEnergy(int $energy): static
    {
        $this->energy = $energy;
        return $this;
    }

    public function getSleepHours(): ?float
    {
        return $this->sleepHours;
    }

    public function setSleepHours(float $sleepHours): static
    {
        $this->sleepHours = $sleepHours;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
