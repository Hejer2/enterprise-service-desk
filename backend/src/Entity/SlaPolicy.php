<?php

namespace App\Entity;

use App\Repository\SlaPolicyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SlaPolicyRepository::class)]
#[ORM\Table(name: 'sla_policies')]
#[ORM\HasLifecycleCallbacks]
class SlaPolicy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sla:read', 'ticket:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['sla:read', 'ticket:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['sla:read', 'ticket:read'])]
    private ?string $priority = null;

    #[ORM\Column]
    #[Assert\GreaterThan(0)]
    #[Groups(['sla:read', 'ticket:read'])]
    private int $firstResponseMinutes = 30;

    #[ORM\Column]
    #[Assert\GreaterThan(0)]
    #[Groups(['sla:read', 'ticket:read'])]
    private int $resolutionMinutes = 480;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 99)]
    #[Groups(['sla:read', 'ticket:read'])]
    private int $warningPercentage = 80;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['sla:read', 'ticket:read'])]
    private bool $isActive = true;

    #[ORM\Column]
    #[Groups(['sla:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['sla:read'])]
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

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getFirstResponseMinutes(): int
    {
        return $this->firstResponseMinutes;
    }

    public function setFirstResponseMinutes(int $firstResponseMinutes): self
    {
        $this->firstResponseMinutes = $firstResponseMinutes;
        return $this;
    }

    public function getResolutionMinutes(): int
    {
        return $this->resolutionMinutes;
    }

    public function setResolutionMinutes(int $resolutionMinutes): self
    {
        $this->resolutionMinutes = $resolutionMinutes;
        return $this;
    }

    public function getWarningPercentage(): int
    {
        return $this->warningPercentage;
    }

    public function setWarningPercentage(int $warningPercentage): self
    {
        $this->warningPercentage = $warningPercentage;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
