<?php

namespace App\Entity;

use App\Repository\TacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Le titre doit contenir au moins 3 caractères")]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: "La deadline est obligatoire")]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    private ?string $statut = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La priorité est obligatoire")]
    private ?string $priorite = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "L'employé est obligatoire")]
    private ?int $employeId = null;

    #[ORM\Column(nullable: true)]
    private ?int $projetId = null;

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }
    
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    
    public function getDeadline(): ?\DateTimeInterface { return $this->deadline; }
    public function setDeadline(?\DateTimeInterface $deadline): self { $this->deadline = $deadline; return $this; }
    
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    
    public function getPriorite(): ?string { return $this->priorite; }
    public function setPriorite(string $priorite): self { $this->priorite = $priorite; return $this; }
    
    public function getEmployeId(): ?int { return $this->employeId; }
    public function setEmployeId(?int $employeId): self { $this->employeId = $employeId; return $this; }
    
    public function getProjetId(): ?int { return $this->projetId; }
    public function setProjetId(?int $projetId): self { $this->projetId = $projetId; return $this; }
}