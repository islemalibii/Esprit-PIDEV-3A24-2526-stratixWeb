<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    private string $prix;

    #[ORM\Column]
    #[Assert\NotBlank]
    private int $delaiTransportJours;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private \DateTimeInterface $dateOffre;

    /**
     * CORRECTION : Ajout de onDelete: 'CASCADE' pour synchroniser 
     * la base de données avec le cascade="remove" de l'entité Fournisseur.
     */
    #[ORM\ManyToOne(targetEntity: Fournisseur::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] 
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(targetEntity: Ressource::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ressource $ressource = null;

    // ... Getters et Setters (inchangés)
    public function getId(): ?int { return $this->id; }
    public function getPrix(): string { return $this->prix; }
    public function setPrix(string|float $prix): static { $this->prix = (string) $prix; return $this; }
    public function getDelaiTransportJours(): int { return $this->delaiTransportJours; }
    public function setDelaiTransportJours(int $delai): static { $this->delaiTransportJours = $delai; return $this; }
    public function getDateOffre(): \DateTimeInterface { return $this->dateOffre; }
    public function setDateOffre(\DateTimeInterface $date): static { $this->dateOffre = $date; return $this; }
    public function getFournisseur(): ?Fournisseur { return $this->fournisseur; }
    public function setFournisseur(?Fournisseur $fournisseur): static { $this->fournisseur = $fournisseur; return $this; }
    public function getRessource(): ?Ressource { return $this->ressource; }
    public function setRessource(?Ressource $ressource): static { $this->ressource = $ressource; return $this; }
}