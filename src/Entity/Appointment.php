<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use App\Validator\NoOverlap;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointment')]
#[NoOverlap]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_rdv', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date du rendez-vous est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date du rendez-vous ne peut pas être dans le passé.")]
    private ?\DateTimeInterface $dateRdv = null;

    #[ORM\Column(name: 'heure_rdv', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure du rendez-vous est obligatoire.")]
    private ?\DateTimeInterface $heureRdv = null;

    #[ORM\Column(name: 'statut', length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private ?string $statut = 'En attente';

    #[ORM\Column(name: 'motif', type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le motif du rendez-vous est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "Le motif doit comporter au moins {{ limit }} caractères.",
        maxMessage: "Le motif ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $motif = null;

    #[ORM\Column(name: 'type_rdv', length: 255)]
    #[Assert\NotBlank(message: "Le type de rendez-vous est obligatoire.")]
    private ?string $typeRdv = null;

    #[ORM\Column(name: 'user_id')]
    #[Assert\NotNull(message: "L'identifiant de l'utilisateur est obligatoire.")]
    private ?int $userId = null;

    #[ORM\Column(name: 'professional_id', type: Types::INTEGER, nullable: true)]
    #[Assert\NotNull(message: "Veuillez choisir un professionnel.")]
    private ?int $professionalId = null;

    #[ORM\Column(name: 'professional_name', length: 255, nullable: true)]
    private ?string $professionalName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRdv(): ?\DateTimeInterface
    {
        return $this->dateRdv;
    }

    public function setDateRdv(\DateTimeInterface $dateRdv): static
    {
        $this->dateRdv = $dateRdv;

        return $this;
    }

    public function getHeureRdv(): ?\DateTimeInterface
    {
        return $this->heureRdv;
    }

    public function setHeureRdv(\DateTimeInterface $heureRdv): static
    {
        $this->heureRdv = $heureRdv;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getTypeRdv(): ?string
    {
        return $this->typeRdv;
    }

    public function setTypeRdv(string $typeRdv): static
    {
        $this->typeRdv = $typeRdv;

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

    public function getProfessionalId(): ?int
    {
        return $this->professionalId;
    }

    public function setProfessionalId(?int $professionalId): static
    {
        $this->professionalId = $professionalId;

        return $this;
    }

    public function getProfessionalName(): ?string
    {
        return $this->professionalName;
    }

    public function setProfessionalName(?string $professionalName): static
    {
        $this->professionalName = $professionalName;

        return $this;
    }
}
