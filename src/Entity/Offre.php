<?php

namespace App\Entity;

use App\Repository\OffreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column]
    private ?int $delaiTransportJours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateOffre = null;

    // Utilisation du FQCN (Full Qualified Class Name) pour éviter l'erreur "target-entity not found"
    #[ORM\ManyToOne(targetEntity: \App\Entity\Fournisseur::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Ressource::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ressource $ressource = null;

    public function getId(): ?int { return $this->id; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(float $prix): static { $this->prix = $prix; return $this; }

    public function getDelaiTransportJours(): ?int { return $this->delaiTransportJours; }
    public function setDelaiTransportJours(int $delai): static { $this->delaiTransportJours = $delai; return $this; }

    public function getDateOffre(): ?\DateTimeInterface { return $this->dateOffre; }
    public function setDateOffre(\DateTimeInterface $date): static { $this->dateOffre = $date; return $this; }

    public function getFournisseur(): ?Fournisseur { return $this->fournisseur; }
    public function setFournisseur(?Fournisseur $fournisseur): static { $this->fournisseur = $fournisseur; return $this; }

    public function getRessource(): ?Ressource { return $this->ressource; }
    public function setRessource(?Ressource $ressource): static { $this->ressource = $ressource; return $this; }
}