<?php

namespace App\Entity;

use App\Repository\BadgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BadgeRepository::class)]
class Badge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $icone = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $seuil = null;

    #[ORM\Column(length: 50)]
    private ?string $categorie = null;

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function getIcone(): ?string { return $this->icone; }
    public function getDescription(): ?string { return $this->description; }
    public function getSeuil(): ?int { return $this->seuil; }
    public function getCategorie(): ?string { return $this->categorie; }

    // Setters
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function setIcone(string $icone): self { $this->icone = $icone; return $this; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function setSeuil(int $seuil): self { $this->seuil = $seuil; return $this; }
    public function setCategorie(string $categorie): self { $this->categorie = $categorie; return $this; }
}