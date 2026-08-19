<?php

namespace App\Entity;

use App\Repository\TicketSlaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketSlaRepository::class)]
#[ORM\Table(name: 'ticket_slas')]
#[ORM\HasLifecycleCallbacks]
class TicketSla
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: SlaPolicy::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?SlaPolicy $slaPolicy = null;

    #[ORM\Column]
    #[Groups(['ticket:read', 'sla:read'])]
    private \DateTimeImmutable $firstResponseDueAt;

    #[ORM\Column]
    #[Groups(['ticket:read', 'sla:read'])]
    private \DateTimeImmutable $resolutionDueAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?\DateTimeImmutable $firstResponseCompletedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?\DateTimeImmutable $resolutionCompletedAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'ACTIVE'])]
    #[Groups(['ticket:read', 'sla:read'])]
    private string $firstResponseStatus = 'ACTIVE';

    #[ORM\Column(length: 20, options: ['default' => 'ACTIVE'])]
    #[Groups(['ticket:read', 'sla:read'])]
    private string $resolutionStatus = 'ACTIVE';

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?\DateTimeImmutable $warningSentAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?\DateTimeImmutable $breachedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
    private ?\DateTimeImmutable $pausedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['ticket:read', 'sla:read'])]
    private int $totalPausedMinutes = 0;

    #[ORM\Column]
    #[Groups(['ticket:read', 'sla:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['ticket:read', 'sla:read'])]
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

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getSlaPolicy(): ?SlaPolicy
    {
        return $this->slaPolicy;
    }

    public function setSlaPolicy(?SlaPolicy $slaPolicy): self
    {
        $this->slaPolicy = $slaPolicy;
        return $this;
    }

    public function getFirstResponseDueAt(): \DateTimeImmutable
    {
        return $this->firstResponseDueAt;
    }

    public function setFirstResponseDueAt(\DateTimeImmutable $firstResponseDueAt): self
    {
        $this->firstResponseDueAt = $firstResponseDueAt;
        return $this;
    }

    public function getResolutionDueAt(): \DateTimeImmutable
    {
        return $this->resolutionDueAt;
    }

    public function setResolutionDueAt(\DateTimeImmutable $resolutionDueAt): self
    {
        $this->resolutionDueAt = $resolutionDueAt;
        return $this;
    }

    public function getFirstResponseCompletedAt(): ?\DateTimeImmutable
    {
        return $this->firstResponseCompletedAt;
    }

    public function setFirstResponseCompletedAt(?\DateTimeImmutable $firstResponseCompletedAt): self
    {
        $this->firstResponseCompletedAt = $firstResponseCompletedAt;
        return $this;
    }

    public function getResolutionCompletedAt(): ?\DateTimeImmutable
    {
        return $this->resolutionCompletedAt;
    }

    public function setResolutionCompletedAt(?\DateTimeImmutable $resolutionCompletedAt): self
    {
        $this->resolutionCompletedAt = $resolutionCompletedAt;
        return $this;
    }

    public function getFirstResponseStatus(): string
    {
        return $this->firstResponseStatus;
    }

    public function setFirstResponseStatus(string $firstResponseStatus): self
    {
        $this->firstResponseStatus = $firstResponseStatus;
        return $this;
    }

    public function getResolutionStatus(): string
    {
        return $this->resolutionStatus;
    }

    public function setResolutionStatus(string $resolutionStatus): self
    {
        $this->resolutionStatus = $resolutionStatus;
        return $this;
    }

    public function getWarningSentAt(): ?\DateTimeImmutable
    {
        return $this->warningSentAt;
    }

    public function setWarningSentAt(?\DateTimeImmutable $warningSentAt): self
    {
        $this->warningSentAt = $warningSentAt;
        return $this;
    }

    public function getBreachedAt(): ?\DateTimeImmutable
    {
        return $this->breachedAt;
    }

    public function setBreachedAt(?\DateTimeImmutable $breachedAt): self
    {
        $this->breachedAt = $breachedAt;
        return $this;
    }

    public function getPausedAt(): ?\DateTimeImmutable
    {
        return $this->pausedAt;
    }

    public function setPausedAt(?\DateTimeImmutable $pausedAt): self
    {
        $this->pausedAt = $pausedAt;
        return $this;
    }

    public function getTotalPausedMinutes(): int
    {
        return $this->totalPausedMinutes;
    }

    public function setTotalPausedMinutes(int $totalPausedMinutes): self
    {
        $this->totalPausedMinutes = $totalPausedMinutes;
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
