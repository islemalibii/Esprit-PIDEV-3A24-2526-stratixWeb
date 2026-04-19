<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // info, warning, success, danger

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?int $relatedId = null; // id de la tâche ou planning

    #[ORM\Column(length: 50)]
    private ?string $relatedType = null; // 'tache', 'planning'

    #[ORM\Column]
    private ?bool $isRead = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function getRelatedId(): ?int { return $this->relatedId; }
    public function setRelatedId(int $relatedId): self { $this->relatedId = $relatedId; return $this; }
    public function getRelatedType(): ?string { return $this->relatedType; }
    public function setRelatedType(string $relatedType): self { $this->relatedType = $relatedType; return $this; }
    public function isRead(): ?bool { return $this->isRead; }
    public function setIsRead(bool $isRead): self { $this->isRead = $isRead; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}