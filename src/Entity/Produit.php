<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** 
     * @var int|null 
     */
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    private string $nom = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $prix = '0.00';

    #[ORM\Column]
    private int $stock_actuel = 0;

    #[ORM\Column]
    private int $stock_min = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fabrication = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_peremption = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_path = null;

    // --- GETTERS ---

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getNom(): string 
    { 
        return $this->nom; 
    }

    public function getDescription(): ?string 
    { 
        return $this->description; 
    }

    public function getCategorie(): ?string 
    { 
        return $this->categorie; 
    }

    public function getPrix(): string 
    { 
        return $this->prix; 
    }

    public function getStockActuel(): int 
    { 
        return $this->stock_actuel; 
    }

    public function getStockMin(): int 
    { 
        return $this->stock_min; 
    }

    public function getDateCreation(): ?\DateTimeInterface 
    { 
        return $this->date_creation; 
    }

    public function getDateFabrication(): ?\DateTimeInterface 
    { 
        return $this->date_fabrication; 
    }

    public function getDatePeremption(): ?\DateTimeInterface 
    { 
        return $this->date_peremption; 
    }

    public function getImagePath(): ?string 
    { 
        return $this->image_path; 
    }

    // --- SETTERS ---

    public function setNom(string $nom): self 
    { 
        $this->nom = $nom; 
        return $this; 
    }

    public function setDescription(?string $description): self 
    { 
        $this->description = $description; 
        return $this; 
    }

    public function setCategorie(?string $categorie): self 
    { 
        $this->categorie = $categorie; 
        return $this; 
    }

    public function setPrix(string $prix): self 
    { 
        $this->prix = $prix; 
        return $this; 
    }

    public function setStockActuel(int $stock): self 
    { 
        $this->stock_actuel = $stock; 
        return $this; 
    }

    public function setStockMin(int $min): self 
    { 
        $this->stock_min = $min; 
        return $this; 
    }

    public function setDateCreation(?\DateTimeInterface $date): self 
    { 
        $this->date_creation = $date; 
        return $this; 
    }

    public function setDateFabrication(?\DateTimeInterface $date): self 
    { 
        $this->date_fabrication = $date; 
        return $this; 
    }

    public function setDatePeremption(?\DateTimeInterface $date): self 
    { 
        $this->date_peremption = $date; 
        return $this; 
    }

    public function setImagePath(?string $path): self 
    { 
        $this->image_path = $path; 
        return $this; 
    }

    /**
     * Logique métier pour l'affichage du statut (utile pour Twig)
     * @return array{text: string, class: string}
     */
    public function getStatut(): array 
    {
        $now = new \DateTime();
        if ($this->date_peremption && $this->date_peremption < $now) {
            return ['text' => '⚠ PÉRIMÉ', 'class' => 'bg-danger-subtle text-danger'];
        }
        if ($this->stock_actuel <= $this->stock_min) {
            return ['text' => '⚠ Stock faible', 'class' => 'bg-warning-subtle text-warning'];
        }
        return ['text' => '✓ En stock', 'class' => 'bg-success-subtle text-success'];
    }
}