<?php

namespace App\Entity;

use App\Repository\ApprovalRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ApprovalRequestRepository::class)]
#[ORM\Table(name: 'approval_requests')]
#[ORM\Index(name: 'idx_ar_ticket', columns: ['ticket_id'])]
#[ORM\Index(name: 'idx_ar_status', columns: ['status'])]
#[ORM\Index(name: 'idx_ar_approver', columns: ['approver_id'])]
class ApprovalRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['approval:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['approval:read'])]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['approval:read'])]
    private ?User $requestedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['approval:read'])]
    private ?User $approver = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    #[Groups(['approval:read'])]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['approval:read'])]
    private ?string $reason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['approval:read'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['approval:read'])]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['approval:read'])]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
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

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): self
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getApprover(): ?User
    {
        return $this->approver;
    }

    public function setApprover(?User $approver): self
    {
        $this->approver = $approver;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        if ($this->status !== 'PENDING' && $this->respondedAt === null) {
            $this->respondedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): self
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }
}
