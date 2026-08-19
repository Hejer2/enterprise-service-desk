<?php

namespace App\Entity;

use App\Repository\TicketDependencyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketDependencyRepository::class)]
#[ORM\Table(name: 'ticket_dependencies')]
#[ORM\UniqueConstraint(name: 'unique_ticket_dependency', columns: ['ticket_id', 'depends_on_ticket_id', 'dependency_type'])]
class TicketDependency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['dependency:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['dependency:read'])]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['dependency:read'])]
    private ?Ticket $dependsOnTicket = null;

    #[ORM\Column(length: 30, options: ['default' => 'BLOCKED_BY'])]
    #[Groups(['dependency:read'])]
    private string $dependencyType = 'BLOCKED_BY';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['dependency:read'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    #[Groups(['dependency:read'])]
    private \DateTimeImmutable $createdAt;

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

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getDependsOnTicket(): ?Ticket
    {
        return $this->dependsOnTicket;
    }

    public function setDependsOnTicket(?Ticket $dependsOnTicket): self
    {
        $this->dependsOnTicket = $dependsOnTicket;
        return $this;
    }

    public function getDependencyType(): string
    {
        return $this->dependencyType;
    }

    public function setDependencyType(string $dependencyType): self
    {
        $this->dependencyType = strtoupper($dependencyType);
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
}
