<?php

namespace App\Entity;

use App\Repository\ExerciceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
#[ORM\Table(name: 'exercise')]
class Exercice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_exercise')]
    private ?int $id = null;

    #[ORM\Column(name: "title", length: 150)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: 'Le titre doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $titre = null;

    #[ORM\Column(name: "description", type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est requise.')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'La description doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = '';

    #[ORM\Column(name: "type", length: 50)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    #[Assert\Choice(
        choices: ['cardio', 'musculation', 'yoga', 'flexibilite', 'sport_collectif', 'autre'],
        message: 'Veuillez choisir un type d\'exercice valide.'
    )]
    private ?string $type = null;

    #[ORM\Column(name: "durationminutes")]
    #[Assert\NotBlank(message: 'La durée est obligatoire.')]
    #[Assert\Range(
        min: 1,
        max: 600,
        notInRangeMessage: 'La durée doit être comprise entre {{ min }} et {{ max }} minutes.'
    )]
    private ?int $duree = null; // en minutes

    #[ORM\Column(name: "difficulty", length: 24)]
    #[Assert\NotBlank(message: 'La difficulté est obligatoire.')]
    #[Assert\Choice(
        choices: ['facile', 'moyen', 'difficile'],
        message: 'Veuillez choisir une difficulté valide.'
    )]
    private ?string $difficulte = null;

    #[ORM\Column(name: "createdat", type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: "updatedat", type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: "user_id", nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: "ispublished", type: Types::SMALLINT, options: ['default' => 0])]
    private int $isPublished = 0;

    #[ORM\ManyToOne(inversedBy: 'exercices')]
    #[ORM\JoinColumn(name: 'objectiveid', referencedColumnName: 'id_objective', nullable: false)]
    private ?Objectif $objectif = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(string $difficulte): static
    {
        $this->difficulte = $difficulte;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function isPublished(): bool
    {
        return (bool) $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished ? 1 : 0;
        return $this;
    }

    public function getObjectif(): ?Objectif
    {
        return $this->objectif;
    }

    public function setObjectif(?Objectif $objectif): static
    {
        $this->objectif = $objectif;
        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}
