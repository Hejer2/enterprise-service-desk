<?php

namespace App\Entity;

use App\Repository\TicketMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketMessageRepository::class)]
#[ORM\Table(name: 'ticket_messages')]
#[ORM\HasLifecycleCallbacks]
class TicketMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['message:read', 'ticket:read'])]
    private ?User $sender = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['message:read', 'ticket:read'])]
    private ?string $message = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['message:read', 'ticket:read'])]
    private bool $isEdited = false;

    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: TicketAttachment::class, mappedBy: 'message', cascade: ['remove'])]
    #[Groups(['message:read', 'ticket:read'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
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

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function isEdited(): bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(bool $isEdited): static
    {
        $this->isEdited = $isEdited;
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

    /**
     * @return Collection<int, TicketAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(TicketAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setMessage($this);
        }

        return $this;
    }

    public function removeAttachment(TicketAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getMessage() === $this) {
                $attachment->setMessage(null);
            }
        }

        return $this;
    }

    public function canBeEditedOrDeletedBy(User $user): bool
    {
        if ($this->getSender() === null || $this->getSender()->getId() !== $user->getId()) {
            return false;
        }
        if ($this->getTicket() === null) {
            return false;
        }
        $status = $this->getTicket()->getStatus();
        if ($status === 'Closed' || $status === 'Resolved') {
            return false;
        }
        return true;
    }
}
