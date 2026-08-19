<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_logs')]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['log:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['log:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    #[Groups(['log:read'])]
    private ?string $action = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $entityName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['log:read'])]
    private ?int $entityId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['log:read'])]
    private ?array $details = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups(['log:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column]
    #[Groups(['log:read'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(?string $entityName): static
    {
        $this->entityName = $entityName;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
