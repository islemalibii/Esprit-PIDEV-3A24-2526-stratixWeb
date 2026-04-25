<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[UniqueEntity(fields: ['nom'], message: "Ce nom de projet est déjà utilisé.")]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // --- CORRECTION : Nom de variable corrigé et relation cible Phase ---
    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Phase::class, cascade: ['persist', 'remove'])]
    private Collection $phases;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Le nom du projet est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom est trop court (min {{ limit }} caractères).",
        maxMessage: "Le nom est trop long (max {{ limit }} caractères)."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\-]+$/",
        message: "Le nom ne doit contenir que des lettres, chiffres et tirets."
    )]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins 10 caractères.")]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\Expression(
        "this.getDateFin() > this.getDateDebut()",
        message: "La date de fin doit être postérieure à la date de début."
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le budget est obligatoire.")]
    #[Assert\Positive(message: "Le budget doit être un nombre positif.")]
    #[Assert\LessThan(value: 10000000, message: "Le budget ne peut pas dépasser 10 000 000 DT.")]
    private ?float $budget = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = "Planifié";

    #[ORM\Column]
    private ?bool $isArchived = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cahierDesCharges = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Veuillez sélectionner un responsable.")]
    private ?Utilisateur $responsable = null;

    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    private Collection $membres;

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'application/x-pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png'
        ],
        maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
        mimeTypesMessage: 'Seuls les formats PDF, DOCX, JPG et PNG sont autorisés.'
    )]
    private ?File $cahierDesChargesFile = null;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->phases = new ArrayCollection(); // CORRIGÉ : correspond à l'attribut
        $this->statut = "Planifié";
        $this->isArchived = false;
        $this->dateDebut = new \DateTime();
    }

    // --- GETTERS / SETTERS ---

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): self { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): self { $this->dateFin = $dateFin; return $this; }
    public function getBudget(): ?float { return $this->budget; }
    public function setBudget(?float $budget): self { $this->budget = $budget; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut ?? 'Planifié'; return $this; }
    public function isIsArchived(): ?bool { return $this->isArchived; }
    public function setIsArchived(bool $isArchived): self { $this->isArchived = $isArchived; return $this; }
    public function getCahierDesCharges(): ?string { return $this->cahierDesCharges; }
    public function setCahierDesCharges(?string $cahierDesCharges): self { $this->cahierDesCharges = $cahierDesCharges; return $this; }
    public function getCahierDesChargesFile(): ?File { return $this->cahierDesChargesFile; }
    public function setCahierDesChargesFile(?File $file): self { $this->cahierDesChargesFile = $file; return $this; }
    public function getResponsable(): ?Utilisateur { return $this->responsable; }
    public function setResponsable(?Utilisateur $responsable): self { $this->responsable = $responsable; return $this; }

    public function getMembres(): Collection { return $this->membres; }
    public function addMembre(Utilisateur $membre): self {
        if (!$this->membres->contains($membre)) { $this->membres->add($membre); }
        return $this;
    }
    public function removeMembre(Utilisateur $membre): self {
        $this->membres->removeElement($membre);
        return $this;
    }

    // --- LOGIQUE POUR LES PHASES (CORRIGÉE) ---

    /** @return Collection<int, Phase> */
    public function getPhases(): Collection
    {
        return $this->phases;
    }

    public function addPhase(Phase $phase): self
    {
        if (!$this->phases->contains($phase)) {
            $this->phases->add($phase);
            $phase->setProjet($this);
        }
        return $this;
    }

    public function removePhase(Phase $phase): self
    {
        if ($this->phases->removeElement($phase)) {
            if ($phase->getProjet() === $this) {
                $phase->setProjet(null);
            }
        }
        return $this;
    }

    // Alias pour la compatibilité avec tes anciens templates Twig qui utilisent .sprints
    public function getSprints(): Collection
    {
        return $this->getPhases();
    }
}