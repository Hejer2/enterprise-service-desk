<?php

namespace App\Entity;

use App\Repository\LeaveTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeaveTypeRepository::class)]
#[ORM\Table(name: 'leave_types')]
class LeaveType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ticket:read', 'leave:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['ticket:read', 'leave:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['ticket:read', 'leave:read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['ticket:read', 'leave:read'])]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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
}
