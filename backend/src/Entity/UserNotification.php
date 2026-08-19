<?php

namespace App\Entity;

use App\Repository\UserNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: 'user_notifications')]
#[ORM\Index(name: 'idx_un_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_un_is_read', columns: ['is_read'])]
#[ORM\Index(name: 'idx_un_created_at', columns: ['created_at'])]
class UserNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Groups(['notification:read'])]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification:read'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['notification:read'])]
    private ?string $message = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $entityType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification:read'])]
    private ?int $entityId = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['notification:read'])]
    private bool $isRead = false;

    #[ORM\Column]
    #[Groups(['notification:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function isIsRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
