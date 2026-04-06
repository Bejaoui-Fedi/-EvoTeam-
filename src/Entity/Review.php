<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct()
    {
        $this->reviewDate = new \DateTime();
    }

    #[ORM\Column]
    #[Assert\NotBlank(message: "La note est obligatoire")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "La note doit être comprise entre {{ min }} et {{ max }}"
    )]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 5,
        minMessage: "Le commentaire doit avoir au moins {{ limit }} caractères"
    )]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de l'avis est obligatoire")]
    private ?\DateTimeInterface $reviewDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        minMessage: "Le titre doit avoir au moins {{ limit }} caractères"
    )]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'événement est obligatoire")]
    private ?Evenement $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): static { $this->rating = $rating; return $this; }
    
    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): static { $this->comment = $comment; return $this; }
    
    public function getReviewDate(): ?\DateTimeInterface { return $this->reviewDate; }
    public function setReviewDate(?\DateTimeInterface $reviewDate): static { $this->reviewDate = $reviewDate; return $this; }
    
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }
    
    public function getEvent(): ?Evenement { return $this->event; }
    public function setEvent(?Evenement $event): static { $this->event = $event; return $this; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }
}