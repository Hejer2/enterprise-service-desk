<?php

namespace App\Entity;

use App\Repository\CsatRatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CsatRatingRepository::class)]
#[ORM\Table(name: 'csat_ratings')]
#[ORM\UniqueConstraint(name: 'unique_ticket_user_csat', columns: ['ticket_id', 'user_id'])]
#[ORM\HasLifecycleCallbacks]
class CsatRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read', 'csat:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['csat:read'])]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['csat:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['ticket:read', 'csat:read'])]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ticket:read', 'csat:read'])]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['ticket:read', 'csat:read'])]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
