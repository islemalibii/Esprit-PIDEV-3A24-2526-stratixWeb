<?php
 
namespace App\Entity;
 
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
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
 
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Email(message: 'Email invalide.')]
    private ?string $email = null;
 
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Le téléphone doit contenir 8 chiffres.')]
    private ?string $tel = null;
 
    public function getTel(): ?string { return $this->tel; }
    public function setTel(?string $tel): self { $this->tel = $tel; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;
 
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): self { $this->password = $password; return $this; }
 
    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Minimum 2 caractères.', maxMessage: 'Maximum 50 caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le nom ne doit contenir que des lettres.')]
    private ?string $nom = null;
 
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
 
    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 50, minMessage: 'Minimum 2 caractères.', maxMessage: 'Maximum 50 caractères.')]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/', message: 'Le prénom ne doit contenir que des lettres.')]
    private ?string $prenom = null;
 
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }
 
    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: 'Le CIN est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Le CIN doit contenir exactement 8 chiffres.')]
    private ?int $cin = null;
 
    public function getCin(): ?int { return $this->cin; }
    public function setCin(int $cin): self { $this->cin = $cin; return $this; }
 
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_ajout = null;
 
    // ✅ FIX: DateTimeInterface
    public function getDateAjout(): ?\DateTimeInterface { return $this->date_ajout; }
    public function setDateAjout(?\DateTimeInterface $date_ajout): static { $this->date_ajout = $date_ajout; return $this; }
 
    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;
 
    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;
 
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): self { $this->statut = $statut; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $department = null;
 
    public function getDepartment(): ?string { return $this->department; }
    public function setDepartment(?string $department): self { $this->department = $department; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $poste = null;
 
    public function getPoste(): ?string { return $this->poste; }
    public function setPoste(?string $poste): self { $this->poste = $poste; return $this; }
 
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date_embauche = null;
 
    // ✅ FIX: DateTimeInterface
    public function getDateEmbauche(): ?\DateTimeInterface { return $this->date_embauche; }
    public function setDateEmbauche(?\DateTimeInterface $date_embauche): static { $this->date_embauche = $date_embauche; return $this; }
 
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $competences = null;
 
    public function getCompetences(): ?string { return $this->competences; }
    public function setCompetences(?string $competences): self { $this->competences = $competences; return $this; }
 
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le salaire doit être positif.')]
    private ?float $salaire = null;
 
    public function getSalaire(): ?float { return $this->salaire; }
    public function setSalaire(?float $salaire): self { $this->salaire = $salaire; return $this; }
 
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $failed_login_attempts = null;
 
    public function getFailedLoginAttempts(): ?int { return $this->failed_login_attempts; }
    public function setFailedLoginAttempts(?int $failed_login_attempts): static { $this->failed_login_attempts = $failed_login_attempts; return $this; }
 
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $account_locked = null;
 
    public function isAccountLocked(): ?bool { return $this->account_locked; }
    public function setAccountLocked(?bool $account_locked): static { $this->account_locked = $account_locked; return $this; }
 
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $locked_until = null;
 
    // ✅ FIX: DateTimeInterface
    public function getLockedUntil(): ?\DateTimeInterface { return $this->locked_until; }
    public function setLockedUntil(?\DateTimeInterface $locked_until): static { $this->locked_until = $locked_until; return $this; }
 
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $two_factor_enabled = null;
 
    public function isTwoFactorEnabled(): ?bool { return $this->two_factor_enabled; }
    public function setTwoFactorEnabled(?bool $two_factor_enabled): static { $this->two_factor_enabled = $two_factor_enabled; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $two_factor_secret = null;
 
    public function getTwoFactorSecret(): ?string { return $this->two_factor_secret; }
    public function setTwoFactorSecret(?string $two_factor_secret): static { $this->two_factor_secret = $two_factor_secret; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatar = null;
 
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true, options: ['default' => 'light'])]
    private string $theme = 'light';
 
    // ✅ FIX: theme is never null, remove nullable
    public function getTheme(): string { return $this->theme; }
    public function setTheme(string $theme): static { $this->theme = $theme; return $this; }
 
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $locked_at = null;
 
    public function getLockedAt(): ?\DateTime { return $this->locked_at; }
    public function setLockedAt(?\DateTime $locked_at): static { $this->locked_at = $locked_at; return $this; }
 
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updated_at = null;
 
    public function getUpdatedAt(): ?\DateTime { return $this->updated_at; }
    public function setUpdatedAt(?\DateTime $updated_at): static { $this->updated_at = $updated_at; return $this; }
 
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $last_emotion = null;
 
    public function getLastEmotion(): ?string { return $this->last_emotion; }
    public function setLastEmotion(?string $emotion): static { $this->last_emotion = $emotion; return $this; }
 
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