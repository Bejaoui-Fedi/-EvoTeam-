<?php

namespace App\Entity;

use App\Repository\WellbeingTrackerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WellbeingTrackerRepository::class)]
#[ORM\Table(name: 'wellbeing_tracker')]
#[ORM\Index(name: 'idx_date', columns: ['date'])]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_daily_task_id', columns: ['daily_routine_task_id'])]
class WellbeingTracker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DailyRoutineTask::class)]
    #[ORM\JoinColumn(name: 'daily_routine_task_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\NotFound(action: 'ignore')]
    private ?DailyRoutineTask $routineTask = null;

    // CRITICAL CHANGE: Changed nullable: true to nullable: false
    // Because your database requires a user_id (NOT NULL constraint)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le score d'humeur est requis.")]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: "L'humeur doit être entre {{ min }} et {{ max }}.")]
    private ?int $mood = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le score de stress est requis.")]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: "Le stress doit être entre {{ min }} et {{ max }}.")]
    private ?int $stress = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le score d'énergie est requis.")]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: "L'énergie doit être entre {{ min }} et {{ max }}.")]
    private ?int $energy = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre d'heures de sommeil est requis.")]
    #[Assert\Range(min: 0, max: 24, notInRangeMessage: "Les heures de sommeil doivent être entre {{ min }} et {{ max }}.")]
    private ?float $sleepHours = null;  // Changed from ?int to ?float to match getter/setter

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}