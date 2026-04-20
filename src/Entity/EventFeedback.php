<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EventFeedbackRepository;

#[ORM\Entity(repositoryClass: EventFeedbackRepository::class)]
#[ORM\Table(name: 'event_feedback')]
class EventFeedback
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

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'eventFeedbacks')]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id')]
    private ?Evenement $evenement = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $commentaire = null;

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_feedback = null;

    public function getDate_feedback(): ?\DateTimeInterface
    {
        return $this->date_feedback;
    }

    public function setDate_feedback(?\DateTimeInterface $date_feedback): self
    {
        $this->date_feedback = $date_feedback;
        return $this;
    }

    public function getDateFeedback(): ?\DateTime
    {
        return $this->date_feedback;
    }

    public function setDateFeedback(?\DateTime $date_feedback): static
    {
        $this->date_feedback = $date_feedback;

        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $user_email = null;

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function setUserEmail(?string $user_email): static
    {
        $this->user_email = $user_email;
        return $this;
    }

}
