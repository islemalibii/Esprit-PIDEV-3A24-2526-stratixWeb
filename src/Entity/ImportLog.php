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
    /**
     * @var int|null
     */
    private ?int $id = null;

    /**
     * CORRECTION : Suppression du "?" et du "= null"
     */
    #[ORM\Column(length: 255)]
    private string $fileName;

    /**
     * CORRECTION : Suppression du "?" et du "= null"
     */
    #[ORM\Column(length: 255)]
    private string $senderEmail;

    /**
     * CORRECTION : Suppression du "?" pour correspondre au type datetime_immutable non-nullable
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * CORRECTION : Suppression du "?" et du "= null"
     */
    #[ORM\Column(length: 50)]
    private string $status; 

    public function __construct()
    {
        // Initialisation automatique à la création de l'objet
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    /**
     * CORRECTION : Type de retour string
     */
    public function getFileName(): string 
    { 
        return $this->fileName; 
    }

    public function setFileName(string $fileName): static 
    { 
        $this->fileName = $fileName; 
        return $this; 
    }

    /**
     * CORRECTION : Type de retour string
     */
    public function getSenderEmail(): string 
    { 
        return $this->senderEmail; 
    }

    public function setSenderEmail(string $senderEmail): static 
    { 
        $this->senderEmail = $senderEmail; 
        return $this; 
    }

    /**
     * CORRECTION : Type de retour \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static 
    { 
        $this->createdAt = $createdAt; 
        return $this; 
    }

    /**
     * CORRECTION : Type de retour string
     */
    public function getStatus(): string 
    { 
        return $this->status; 
    }

    public function setStatus(string $status): static 
    { 
        $this->status = $status; 
        return $this; 
    }
}