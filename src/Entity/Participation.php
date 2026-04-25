<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ParticipationRepository;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
class Participation
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $event_id = null;

    public function getEvent_id(): ?int
    {
        return $this->event_id;
    }

    public function setEvent_id(int $event_id): self
    {
        $this->event_id = $event_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $user_email = null;

    public function getUser_email(): ?string
    {
        return $this->user_email;
    }

    public function setUser_email(string $user_email): self
    {
        $this->user_email = $user_email;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $participation_date = null;

    public function getParticipation_date(): ?\DateTimeInterface
    {
        return $this->participation_date;
    }

    public function setParticipation_date(\DateTimeInterface $participation_date): self
    {
        $this->participation_date = $participation_date;
        return $this;
    }

    public function getEventId(): ?int
    {
        return $this->event_id;
    }

    public function setEventId(int $event_id): static
    {
        $this->event_id = $event_id;

        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function setUserEmail(string $user_email): static
    {
        $this->user_email = $user_email;

        return $this;
    }

    public function getParticipationDate(): ?\DateTimeInterface
    {
        return $this->participation_date;
    }

    public function setParticipationDate(\DateTime $participation_date): static
    {
        $this->participation_date = $participation_date;

        return $this;
    }

}
