<?php

namespace App\Entity;

use App\Repository\RecurringTicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RecurringTicketRepository::class)]
#[ORM\Table(name: 'recurring_tickets')]
#[ORM\Index(name: 'idx_rt_is_active', columns: ['is_active'])]
#[ORM\Index(name: 'idx_rt_next_run_at', columns: ['next_run_at'])]
#[ORM\HasLifecycleCallbacks]
class RecurringTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recurring:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recurring:read'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['recurring:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Groups(['recurring:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 50)]
    #[Groups(['recurring:read'])]
    private ?string $priority = 'Medium';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['recurring:read'])]
    private ?User $assignedTo = null;

    #[ORM\Column(length: 20, options: ['default' => 'MONTHLY'])]
    #[Groups(['recurring:read'])]
    private string $frequency = 'MONTHLY';

    #[ORM\Column]
    #[Groups(['recurring:read'])]
    private \DateTimeImmutable $nextRunAt;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['recurring:read'])]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['recurring:read'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    #[Groups(['recurring:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['recurring:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->nextRunAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = strtoupper($frequency);
        return $this;
    }

    public function getNextRunAt(): \DateTimeImmutable
    {
        return $this->nextRunAt;
    }

    public function setNextRunAt(\DateTimeImmutable $nextRunAt): self
    {
        $this->nextRunAt = $nextRunAt;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
