<?php

namespace App\Entity;

use App\Repository\AutomationRuleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AutomationRuleRepository::class)]
#[ORM\Table(name: 'automation_rules')]
#[ORM\Index(name: 'idx_ar_is_active', columns: ['is_active'])]
#[ORM\Index(name: 'idx_ar_trigger_type', columns: ['trigger_type'])]
#[ORM\HasLifecycleCallbacks]
class AutomationRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['automation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['automation:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['automation:read'])]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['automation:read'])]
    private bool $isActive = true;

    #[ORM\Column(length: 50)]
    #[Groups(['automation:read'])]
    private ?string $triggerType = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['automation:read'])]
    private array $conditions = [];

    #[ORM\Column(type: 'json')]
    #[Groups(['automation:read'])]
    private array $actions = [];

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['automation:read'])]
    private int $priority = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['automation:read'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    #[Groups(['automation:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['automation:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    public function getTriggerType(): ?string
    {
        return $this->triggerType;
    }

    public function setTriggerType(string $triggerType): self
    {
        $this->triggerType = strtoupper($triggerType);
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions): self
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
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
