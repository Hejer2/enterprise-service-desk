<?php

namespace App\Entity;

use App\Repository\KnowledgeCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KnowledgeCategoryRepository::class)]
#[ORM\Table(name: 'knowledge_categories')]
#[ORM\HasLifecycleCallbacks]
class KnowledgeCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['kb:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['kb:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Groups(['kb:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['kb:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['kb:read'])]
    private ?string $icon = '📁';

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['kb:read'])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['kb:read'])]
    private int $sortOrder = 0;

    #[ORM\Column]
    #[Groups(['kb:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['kb:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: KnowledgeArticle::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->articles = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        if (!$this->slug) {
            $this->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        }
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon ?: '📁';
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, KnowledgeArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
