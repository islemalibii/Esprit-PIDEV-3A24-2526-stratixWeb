<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le prix ne peut pas être négatif.")]
    private ?float $prix = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le stock ne peut pas être négatif.")]
    private ?int $stock_actuel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le stock minimum ne peut pas être négatif.")]
    private ?int $stock_min = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ressources_necessaires = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image_path = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\LessThanOrEqual("today", message: "La date de fabrication ne peut pas être dans le futur.")]
    private ?\DateTimeInterface $date_fabrication = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\GreaterThan(propertyPath: "date_fabrication", message: "La date de péremption doit être après la date de fabrication.")]
    private ?\DateTimeInterface $date_peremption = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_garantie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    // --- GETTERS ET SETTERS ---
    // Note : Pour éviter l'erreur NoSuchPropertyException, on garde les noms CamelCase 
    // qui sont le standard de Symfony pour accéder aux propriétés avec underscores.

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $categorie): self { $this->categorie = $categorie; return $this; }

    public function getPrix(): ?float { return $this->prix; }
    public function setPrix(?float $prix): self { $this->prix = $prix; return $this; }

    // --- Synchronisation stock_actuel ---
    public function getStockActuel(): ?int { return $this->stock_actuel; }
    public function setStockActuel(?int $stock_actuel): self { $this->stock_actuel = $stock_actuel; return $this; }

    // --- Synchronisation stock_min ---
    public function getStockMin(): ?int { return $this->stock_min; }
    public function setStockMin(?int $stock_min): self { $this->stock_min = $stock_min; return $this; }

    // --- Synchronisation date_creation ---
    public function getDateCreation(): ?\DateTimeInterface { return $this->date_creation; }
    public function setDateCreation(?\DateTimeInterface $date_creation): self { $this->date_creation = $date_creation; return $this; }

    // --- Synchronisation ressources_necessaires ---
    public function getRessourcesNecessaires(): ?string { return $this->ressources_necessaires; }
    public function setRessourcesNecessaires(?string $ressources_necessaires): self { $this->ressources_necessaires = $ressources_necessaires; return $this; }

    // --- Synchronisation image_path ---
    public function getImagePath(): ?string { return $this->image_path; }
    public function setImagePath(?string $image_path): self { $this->image_path = $image_path; return $this; }

    // --- Synchronisation date_fabrication ---
    public function getDateFabrication(): ?\DateTimeInterface { return $this->date_fabrication; }
    public function setDateFabrication(?\DateTimeInterface $date_fabrication): self { $this->date_fabrication = $date_fabrication; return $this; }

    // --- Synchronisation date_peremption ---
    public function getDatePeremption(): ?\DateTimeInterface { return $this->date_peremption; }
    public function setDatePeremption(?\DateTimeInterface $date_peremption): self { $this->date_peremption = $date_peremption; return $this; }

    // --- Synchronisation date_garantie ---
    public function getDateGarantie(): ?\DateTimeInterface { return $this->date_garantie; }
    public function setDateGarantie(?\DateTimeInterface $date_garantie): self { $this->date_garantie = $date_garantie; return $this; }

    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): self { $this->details = $details; return $this; }

    // --- LOGIQUE MÉTIER ---
    public function getStatut(): array {
        $now = new \DateTime();
        
        if ($this->date_peremption && $this->date_peremption < $now) {
            return ['text' => '⚠ PRODUIT PÉRIMÉ ⚠', 'class' => 'bg-danger-subtle text-danger'];
        }
        if ($this->stock_actuel !== null && $this->stock_min !== null && $this->stock_actuel <= $this->stock_min) {
            return ['text' => '⚠ Stock faible', 'class' => 'bg-warning-subtle text-warning'];
        }
        
        return ['text' => '✓ Produit en bon état', 'class' => 'bg-success-subtle text-success'];
    }
}