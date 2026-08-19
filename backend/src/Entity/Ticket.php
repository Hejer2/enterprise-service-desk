<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read', 'message:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['ticket:read', 'message:read'])]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['ticket:read', 'message:read'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['ticket:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(['ticket:read'])]
    private ?string $category = null;



    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['ticket:read'])]
    private ?string $priority = null;

    #[ORM\Column(length: 30)]
    #[Groups(['ticket:read'])]
    private ?string $status = 'Open';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['ticket:read'])]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['ticket:read'])]
    private ?User $assignedTo = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\OneToMany(targetEntity: TicketMessage::class, mappedBy: 'ticket', cascade: ['remove'])]
    private Collection $messages;

    #[ORM\OneToMany(targetEntity: TicketAttachment::class, mappedBy: 'ticket', cascade: ['remove'])]
    private Collection $attachments;

    #[ORM\OneToOne(mappedBy: 'ticket', targetEntity: LeaveRequest::class, cascade: ['persist', 'remove'])]
    #[Groups(['ticket:read'])]
    private ?LeaveRequest $leaveRequest = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        if ($this->ticketNumber === null) {
            $this->ticketNumber = 'TCK-' . date('Y') . '-' . sprintf('%06d', rand(1, 999999));
        }
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

    public function getTicketNumber(): ?string
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber(string $ticketNumber): static
    {
        $this->ticketNumber = $ticketNumber;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }



    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === 'Closed' || $status === 'Resolved' || $status === 'Rejected' || $status === 'Approved') {
            $this->closedAt = new \DateTimeImmutable();
        } else {
            $this->closedAt = null;
        }
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
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

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    /**
     * @return Collection<int, TicketMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection<int, TicketAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function getLeaveRequest(): ?LeaveRequest
    {
        return $this->leaveRequest;
    }

    public function setLeaveRequest(?LeaveRequest $leaveRequest): static
    {
        // set the owning side of the relation if necessary
        if ($leaveRequest !== null && $leaveRequest->getTicket() !== $this) {
            $leaveRequest->setTicket($this);
        }

        $this->leaveRequest = $leaveRequest;
        return $this;
    }

    public function canBeEditedOrDeletedBy(User $user): bool
    {
        if ($this->getCreatedBy() === null || $this->getCreatedBy()->getId() !== $user->getId()) {
            return false;
        }
        if ($this->getStatus() !== 'Open') {
            return false;
        }
        foreach ($this->getMessages() as $msg) {
            if ($msg->getSender() !== null && $msg->getSender()->getId() !== $user->getId()) {
                return false;
            }
        }
        return true;
    }
}
