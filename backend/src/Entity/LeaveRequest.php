<?php

namespace App\Entity;

use App\Repository\LeaveRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeaveRequestRepository::class)]
#[ORM\Table(name: 'leave_requests')]
class LeaveRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'leaveRequest', targetEntity: Ticket::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: LeaveType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ticket:read'])]
    private ?LeaveType $leaveType = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['ticket:read'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['ticket:read'])]
    private bool $halfDay = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): static
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getLeaveType(): ?LeaveType
    {
        return $this->leaveType;
    }

    public function setLeaveType(?LeaveType $leaveType): static
    {
        $this->leaveType = $leaveType;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isHalfDay(): bool
    {
        return $this->halfDay;
    }

    public function setHalfDay(bool $halfDay): static
    {
        $this->halfDay = $halfDay;
        return $this;
    }
}
