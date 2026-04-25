<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\EvenementRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
#[UniqueEntity(fields: ['titre'], message: "Un événement avec ce titre existe déjà.")]  // 👈 here on the class
class Evenement
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

    #[ORM\Column(name: 'type_event', type: 'string', nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['reunion', 'formation', 'lancementProduit', 'seminaire', 'recrutement'],
        message: "Type d'événement invalide."
    )]
    private ?string $type_event = null;

    public function getTypeEvent(): ?string
    {
        return $this->type_event;
    }

    public function setTypeEvent(?string $type_event): static
    {
        $this->type_event = $type_event;

        return $this;
    }



    #[ORM\Column(name: 'date_event', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "La date est obligatoire.")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date de l'événement ne peut pas être dans le passé."
    )]
    #[Assert\GreaterThanOrEqual(
        value: "+3 days",
        message: "L'événement doit être planifié au moins 3 jours à l'avance."
    )]
    private ?\DateTimeInterface $date_event = null;

    public function getDateEvent(): ?\DateTime
    {
        return $this->date_event;
    }

    public function setDateEvent(?\DateTime $date_event): static
    {
        $this->date_event = $date_event;

        return $this;
    }



    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez fournir une description.")]
    #[Assert\Length(min: 10, minMessage: "La description est trop courte.")]
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


    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['planifier', 'annuler', 'terminer'],
        message: "Statut invalide."
    )]
    private ?string $statut = 'planifier';

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(name: 'isArchived', type: 'boolean', nullable: true)]
    private ?bool $isArchived = null;

    public function isIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(?bool $isArchived): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->isArchived;
    }


    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: EventFeedback::class, mappedBy: 'evenement')]
    private Collection $eventFeedbacks;

    public function __construct()
    {
        $this->eventFeedbacks = new ArrayCollection();
    }


    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $recurrence = null; // 'none', 'weekly', 'monthly'

    public function getRecurrence(): ?string
    {
        return $this->recurrence;
    }

    public function setRecurrence(?string $recurrence): static
    {
        $this->recurrence = $recurrence;
        return $this;
    }

    /**
     * @return Collection<int, EventFeedback>
     */
    public function getEventFeedbacks(): Collection
    {
        if (!$this->eventFeedbacks instanceof Collection) {
            $this->eventFeedbacks = new ArrayCollection();
        }
        return $this->eventFeedbacks;
    }

    public function addEventFeedback(EventFeedback $eventFeedback): self
    {
        if (!$this->getEventFeedbacks()->contains($eventFeedback)) {
            $this->getEventFeedbacks()->add($eventFeedback);
        }
        return $this;
    }

    public function removeEventFeedback(EventFeedback $eventFeedback): self
    {
        $this->getEventFeedbacks()->removeElement($eventFeedback);
        return $this;
    }

    

    



    #[ORM\Column(name: 'image_url', type: 'string', nullable: true)]
    private ?string $image_url = null;

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

}
