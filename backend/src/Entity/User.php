<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    private ?string $profilePicture = null;



    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?Role $roleEntity = null;

    #[ORM\Column(length: 5, options: ['default' => 'en'])]
    #[Groups(['user:read'])]
    private string $language = 'en';

    #[ORM\Column(length: 10, options: ['default' => 'light'])]
    #[Groups(['user:read'])]
    private string $theme = 'light';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fcmToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $plainPassword = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // Return standard Symfony role name derived from our custom Role entity
        return [$this->roleEntity?->getName() ?? 'ROLE_EMPLOYEE'];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    #[Groups(['user:read', 'ticket:read', 'message:read'])]
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }



    public function getRoleEntity(): ?Role
    {
        return $this->roleEntity;
    }

    public function setRoleEntity(?Role $roleEntity): static
    {
        $this->roleEntity = $roleEntity;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEmailHash(): string
    {
        return md5(strtolower(trim($this->email ?? '')));
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }
}
