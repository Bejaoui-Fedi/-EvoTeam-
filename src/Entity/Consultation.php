<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'consultation')]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Appointment::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'appointment_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "La consultation doit être liée à un rendez-vous.")]
    private ?Appointment $appointment = null;

    #[ORM\Column(name: 'date_consultation', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de consultation est obligatoire.")]
    private ?\DateTimeInterface $dateConsultation = null;

    #[ORM\Column(name: 'diagnostic', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Le diagnostic ne peut pas être vide.")]
    #[Assert\Length(
        min: 5,
        minMessage: "Le diagnostic doit comporter au moins {{ limit }} caractères."
    )]
    private ?string $diagnostic = null;

    #[ORM\Column(name: 'observation', type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(name: 'traitement', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Le traitement doit être spécifié.")]
    private ?string $traitement = null;

    #[ORM\Column(name: 'ordonnance', type: Types::TEXT, nullable: true)]
    private ?string $ordonnance = null;

    #[ORM\Column(name: 'duree', nullable: true)]
    #[Assert\Positive(message: "La durée doit être un nombre positif.")]
    private ?int $duree = null; // en minutes

    #[ORM\Column(name: 'statut_consultation', length: 255)]
    private ?string $statutConsultation = 'Terminée';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(Appointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getDateConsultation(): ?\DateTimeInterface
    {
        return $this->dateConsultation;
    }

    public function setDateConsultation(\DateTimeInterface $dateConsultation): static
    {
        $this->dateConsultation = $dateConsultation;

        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(?string $diagnostic): static
    {
        $this->diagnostic = $diagnostic;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

        return $this;
    }

    public function getTraitement(): ?string
    {
        return $this->traitement;
    }

    public function setTraitement(?string $traitement): static
    {
        $this->traitement = $traitement;

        return $this;
    }

    public function getOrdonnance(): ?string
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?string $ordonnance): static
    {
        $this->ordonnance = $ordonnance;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getStatutConsultation(): ?string
    {
        return $this->statutConsultation;
    }

    public function setStatutConsultation(string $statutConsultation): static
    {
        $this->statutConsultation = $statutConsultation;

        return $this;
    }
}
