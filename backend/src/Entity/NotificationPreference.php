<?php

namespace App\Entity;

use App\Repository\NotificationPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationPreferenceRepository::class)]
#[ORM\Table(name: 'notification_preferences')]
#[ORM\HasLifecycleCallbacks]
class NotificationPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pref:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $ticketAssignments = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $ticketReplies = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $ticketStatusChanges = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $slaAlerts = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $systemNotifications = true;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['pref:read'])]
    private bool $browserNotifications = true;

    #[ORM\Column]
    #[Groups(['pref:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isTicketAssignments(): bool
    {
        return $this->ticketAssignments;
    }

    public function setTicketAssignments(bool $ticketAssignments): self
    {
        $this->ticketAssignments = $ticketAssignments;
        return $this;
    }

    public function isTicketReplies(): bool
    {
        return $this->ticketReplies;
    }

    public function setTicketReplies(bool $ticketReplies): self
    {
        $this->ticketReplies = $ticketReplies;
        return $this;
    }

    public function isTicketStatusChanges(): bool
    {
        return $this->ticketStatusChanges;
    }

    public function setTicketStatusChanges(bool $ticketStatusChanges): self
    {
        $this->ticketStatusChanges = $ticketStatusChanges;
        return $this;
    }

    public function isSlaAlerts(): bool
    {
        return $this->slaAlerts;
    }

    public function setSlaAlerts(bool $slaAlerts): self
    {
        $this->slaAlerts = $slaAlerts;
        return $this;
    }

    public function isSystemNotifications(): bool
    {
        return $this->systemNotifications;
    }

    public function setSystemNotifications(bool $systemNotifications): self
    {
        $this->systemNotifications = $systemNotifications;
        return $this;
    }

    public function isBrowserNotifications(): bool
    {
        return $this->browserNotifications;
    }

    public function setBrowserNotifications(bool $browserNotifications): self
    {
        $this->browserNotifications = $browserNotifications;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
