<?php

namespace App\Entity;

use App\Repository\TicketActivityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketActivityRepository::class)]
#[ORM\Table(name: 'ticket_activities')]
#[ORM\Index(name: 'idx_ticket_activity_ticket_created', columns: ['ticket_id', 'created_at'])]
class TicketActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activity:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['activity:read'])]
    private ?User $actor = null;

    #[ORM\Column(length: 50)]
    #[Groups(['activity:read'])]
    private ?string $eventType = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity:read'])]
    private ?string $previousValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['activity:read'])]
    private ?string $newValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['activity:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['activity:read'])]
    private ?array $metadata = null;

    #[ORM\Column]
    #[Groups(['activity:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): static
    {
        $this->actor = $actor;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getPreviousValue(): ?string
    {
        return $this->previousValue;
    }

    public function setPreviousValue(?string $previousValue): static
    {
        $this->previousValue = $previousValue;
        return $this;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): static
    {
        $this->newValue = $newValue;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Return formatted actor array without sensitive info (no email)
     */
    public function getActorData(): ?array
    {
        if (!$this->actor) {
            return null;
        }

        return [
            'id' => $this->actor->getId(),
            'displayName' => $this->actor->getFullName(),
            'role' => $this->actor->getRoleEntity()?->getName(),
        ];
    }
}
