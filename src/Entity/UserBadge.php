<?php

namespace App\Entity;

use App\Repository\UserBadgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBadgeRepository::class)]
class UserBadge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Badge $badge = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $obtenuLe = null;

    public function __construct()
    {
        $this->obtenuLe = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->userId; }
    public function getBadge(): ?Badge { return $this->badge; }
    public function getObtenuLe(): ?\DateTimeImmutable { return $this->obtenuLe; }

    // Setters
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function setBadge(?Badge $badge): self { $this->badge = $badge; return $this; }
}