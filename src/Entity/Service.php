<?php

namespace App\Entity;

use App\Entity\CategorieService;
use App\Entity\Utilisateur;
use App\Repository\ServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_service = null;

    public function getTypeService(): ?string
    {
        return $this->type_service;
    }

    public function setTypeService(?string $type_service): static
    {
        $this->type_service = $type_service;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $date_creation = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $date_debut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $date_fin = null;

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $utilisateur_id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: true)]
    private ?Utilisateur $utilisateur = null;

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateurId(?int $utilisateur_id): static
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        $this->utilisateur_id = $utilisateur?->getId();
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $budget = null;

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): self
    {
        $this->budget = $budget;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $categorie_id = null;

    #[ORM\ManyToOne(targetEntity: CategorieService::class)]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id', nullable: true)]
    private ?CategorieService $categorie = null;

    public function getCategorieId(): ?int
    {
        return $this->categorie_id;
    }

    public function setCategorieId(?int $categorie_id): static
    {
        $this->categorie_id = $categorie_id;
        return $this;
    }

    public function getCategorie(): ?CategorieService
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieService $categorie): self
    {
        $this->categorie = $categorie;
        $this->categorie_id = $categorie?->getId();
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $archive = null;

    public function isArchive(): ?bool
    {
        return $this->archive;
    }

    public function setArchive(?bool $archive): self
    {
        $this->archive = $archive;
        return $this;
    }
}