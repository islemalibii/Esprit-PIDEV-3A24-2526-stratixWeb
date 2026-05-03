<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use App\Repository\UtilisateurRepository;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['cin'], message: 'Ce CIN est déjà utilisé.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Email(message: 'Email invalide.')]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Le téléphone doit contenir 8 chiffres.')]
    private ?string $tel = null;

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    #[Ignore]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] ?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Minimum 2 caractères.', maxMessage: 'Maximum 50 caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres.')]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Minimum 2 caractères.', maxMessage: 'Maximum 50 caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le prénom ne doit contenir que des lettres.')]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le CIN est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Le CIN doit contenir exactement 8 chiffres.')]
    private ?int $cin = null;

    public function getCin(): ?int
    {
        return $this->cin;
    }

    public function setCin(int $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_ajout = null;

    public function getDate_ajout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDate_ajout(?\DateTimeInterface $date_ajout): self
    {
        $this->date_ajout = $date_ajout;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

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
    private ?string $department = null;

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $poste = null;

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(?string $poste): self
    {
        $this->poste = $poste;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_embauche = null;

    public function getDate_embauche(): ?\DateTimeInterface
    {
        return $this->date_embauche;
    }

    public function setDate_embauche(?\DateTimeInterface $date_embauche): self
    {
        $this->date_embauche = $date_embauche;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $competences = null;

    public function getCompetences(): ?string
    {
        return $this->competences;
    }

    public function setCompetences(?string $competences): self
    {
        $this->competences = $competences;
        return $this;
    }

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le salaire doit être positif.')]
    private ?string $salaire = null;

    public function getSalaire(): ?float
    {
        return $this->salaire !== null ? (float)$this->salaire : null;
    }

    public function setSalaire(?float $salaire): self
    {
        $this->salaire = $salaire !== null ? (string)$salaire : null;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $failed_login_attempts = null;

    public function getFailed_login_attempts(): ?int
    {
        return $this->failed_login_attempts;
    }

    public function setFailed_login_attempts(?int $failed_login_attempts): self
    {
        $this->failed_login_attempts = $failed_login_attempts;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $account_locked = null;

    public function isAccount_locked(): ?bool
    {
        return $this->account_locked;
    }

    public function setAccount_locked(?bool $account_locked): self
    {
        $this->account_locked = $account_locked;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $locked_until = null;

    public function getLocked_until(): ?\DateTimeInterface
    {
        return $this->locked_until;
    }

    public function setLocked_until(?\DateTimeInterface $locked_until): self
    {
        $this->locked_until = $locked_until;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $two_factor_enabled = null;

    public function isTwo_factor_enabled(): ?bool
    {
        return $this->two_factor_enabled;
    }

    public function setTwo_factor_enabled(?bool $two_factor_enabled): self
    {
        $this->two_factor_enabled = $two_factor_enabled;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $two_factor_secret = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', nullable: false, options: ['default' => 'light'])]
    private string $theme = 'light';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $locked_at = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $last_emotion = null;

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

    public function getTheme(): string { return $this->theme; }
    public function setTheme(string $theme): static { $this->theme = $theme; return $this; }

    public function getLockedAt(): ?\DateTimeImmutable { return $this->locked_at; }
    public function setLockedAt(?\DateTimeImmutable $locked_at): static { $this->locked_at = $locked_at; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updated_at; }
    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static { $this->updated_at = $updated_at; return $this; }

    public function getLastEmotion(): ?string { return $this->last_emotion; }
    public function setLastEmotion(?string $emotion): static { $this->last_emotion = $emotion; return $this; }

    #[Ignore]
    public function getTwo_factor_secret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function setTwo_factor_secret(#[\SensitiveParameter] ?string $two_factor_secret): self
    {
        $this->two_factor_secret = $two_factor_secret;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(?\DateTime $date_ajout): static
    {
        $this->date_ajout = $date_ajout;

        return $this;
    }

    public function getDateEmbauche(): ?\DateTimeInterface
    {
        return $this->date_embauche;
    }

    public function setDateEmbauche(?\DateTime $date_embauche): static
    {
        $this->date_embauche = $date_embauche;

        return $this;
    }

    public function getFailedLoginAttempts(): ?int
    {
        return $this->failed_login_attempts;
    }

    public function setFailedLoginAttempts(?int $failed_login_attempts): static
    {
        $this->failed_login_attempts = $failed_login_attempts;

        return $this;
    }

    public function isAccountLocked(): ?bool
    {
        return $this->account_locked;
    }

    public function setAccountLocked(?bool $account_locked): static
    {
        $this->account_locked = $account_locked;

        return $this;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->locked_until;
    }

    public function setLockedUntil(?\DateTime $locked_until): static
    {
        $this->locked_until = $locked_until;

        return $this;
    }

    public function isTwoFactorEnabled(): ?bool
    {
        return $this->two_factor_enabled;
    }

    public function setTwoFactorEnabled(?bool $two_factor_enabled): static
    {
        $this->two_factor_enabled = $two_factor_enabled;

        return $this;
    }

    #[Ignore]
    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function setTwoFactorSecret(#[\SensitiveParameter] ?string $two_factor_secret): static
    {
        $this->two_factor_secret = $two_factor_secret;

        return $this;
    }

    // --- UserInterface ---

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $role = strtoupper(str_replace(' ', '_', $this->role ?? 'user'));
        return ['ROLE_' . $role];
    }

    public function eraseCredentials(): void {}
}