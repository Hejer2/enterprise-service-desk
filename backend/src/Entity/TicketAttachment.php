<?php

namespace App\Entity;

use App\Repository\TicketAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TicketAttachmentRepository::class)]
#[ORM\Table(name: 'ticket_attachments')]
class TicketAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Ticket $ticket = null;

    #[ORM\ManyToOne(targetEntity: TicketMessage::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?TicketMessage $message = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message:read', 'ticket:read'])]
    private ?string $fileName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message:read', 'ticket:read'])]
    private ?string $filePath = null;

    #[ORM\Column(length: 100)]
    #[Groups(['message:read', 'ticket:read'])]
    private ?string $fileType = null;

    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
    private ?int $fileSize = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'ticket:read'])]
    private ?User $uploadedBy = null;

    #[ORM\Column]
    #[Groups(['message:read', 'ticket:read'])]
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

    public function getMessage(): ?TicketMessage
    {
        return $this->message;
    }

    public function setMessage(?TicketMessage $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): static
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
