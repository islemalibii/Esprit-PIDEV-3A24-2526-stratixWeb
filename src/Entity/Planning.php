<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date est obligatoire")]
    #[Assert\GreaterThanOrEqual(value: "today", message: "La date ne peut pas être dans le passé")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type de shift est obligatoire")]
    private ?string $typeShift = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "L'employé est obligatoire")]
    private ?int $employeId = null;

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    
    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(?\DateTimeInterface $date): self { $this->date = $date; return $this; }
    
    public function getHeureDebut(): ?\DateTimeInterface { return $this->heureDebut; }
    public function setHeureDebut(?\DateTimeInterface $heureDebut): self { $this->heureDebut = $heureDebut; return $this; }
    
    public function getHeureFin(): ?\DateTimeInterface { return $this->heureFin; }
    public function setHeureFin(?\DateTimeInterface $heureFin): self { $this->heureFin = $heureFin; return $this; }
    
    public function getTypeShift(): ?string { return $this->typeShift; }
    public function setTypeShift(string $typeShift): self { $this->typeShift = $typeShift; return $this; }
    
    public function getEmployeId(): ?int { return $this->employeId; }
    public function setEmployeId(?int $employeId): self { $this->employeId = $employeId; return $this; }
}