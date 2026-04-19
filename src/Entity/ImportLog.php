<?php

namespace App\Entity;

use App\Repository\ImportLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportLogRepository::class)]
class ImportLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255)]
    private ?string $senderEmail = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null; // Ex: 'SUCCESS', 'ERROR'

    public function __construct()
    {
        // Initialisation automatique à la création de l'objet
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getFileName(): ?string 
    { 
        return $this->fileName; 
    }

    public function setFileName(string $fileName): static 
    { 
        $this->fileName = $fileName; 
        return $this; 
    }

    public function getSenderEmail(): ?string 
    { 
        return $this->senderEmail; 
    }

    public function setSenderEmail(string $senderEmail): static 
    { 
        $this->senderEmail = $senderEmail; 
        return $this; 
    }

    public function getCreatedAt(): ?\DateTimeImmutable 
    { 
        return $this->createdAt; 
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static 
    { 
        $this->createdAt = $createdAt; 
        return $this; 
    }

    public function getStatus(): ?string 
    { 
        return $this->status; 
    }

    public function setStatus(string $status): static 
    { 
        $this->status = $status; 
        return $this; 
    }
}