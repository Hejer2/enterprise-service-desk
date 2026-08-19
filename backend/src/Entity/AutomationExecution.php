<?php

namespace App\Entity;

use App\Repository\AutomationExecutionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AutomationExecutionRepository::class)]
#[ORM\Table(name: 'automation_executions')]
#[ORM\Index(name: 'idx_ae_rule', columns: ['rule_id'])]
#[ORM\Index(name: 'idx_ae_ticket', columns: ['ticket_id'])]
#[ORM\Index(name: 'idx_ae_status', columns: ['status'])]
class AutomationExecution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['automation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AutomationRule::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['automation:read'])]
    private ?AutomationRule $rule = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['automation:read'])]
    private ?Ticket $ticket = null;

    #[ORM\Column(length: 20)]
    #[Groups(['automation:read'])]
    private string $status = 'SUCCESS';

    #[ORM\Column(type: 'json')]
    #[Groups(['automation:read'])]
    private array $actionsExecuted = [];

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['automation:read'])]
    private ?string $errorMessage = null;

    #[ORM\Column]
    #[Groups(['automation:read'])]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['automation:read'])]
    private ?string $executionKey = null;

    public function __construct()
    {
        $this->executedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRule(): ?AutomationRule
    {
        return $this->rule;
    }

    public function setRule(?AutomationRule $rule): self
    {
        $this->rule = $rule;
        return $this;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        return $this;
    }

    public function getActionsExecuted(): array
    {
        return $this->actionsExecuted;
    }

    public function setActionsExecuted(array $actionsExecuted): self
    {
        $this->actionsExecuted = $actionsExecuted;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getExecutionKey(): ?string
    {
        return $this->executionKey;
    }

    public function setExecutionKey(string $executionKey): self
    {
        $this->executionKey = $executionKey;
        return $this;
    }
}
