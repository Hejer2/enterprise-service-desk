<?php

namespace App\Entity;

use App\Repository\KnowledgeArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KnowledgeArticleRepository::class)]
#[ORM\Table(name: 'knowledge_articles')]
#[ORM\Index(name: 'idx_kb_status', columns: ['status'])]
#[ORM\Index(name: 'idx_kb_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_kb_published_at', columns: ['published_at'])]
#[ORM\HasLifecycleCallbacks]
class KnowledgeArticle
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['kb:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['kb:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['kb:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['kb:read'])]
    private ?string $content = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['kb:read'])]
    private ?string $excerpt = null;

    #[ORM\Column(length: 20, options: ['default' => 'DRAFT'])]
    #[Groups(['kb:read'])]
    private string $status = 'DRAFT';

    #[ORM\ManyToOne(targetEntity: KnowledgeCategory::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['kb:read'])]
    private ?KnowledgeCategory $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['kb:read'])]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['kb:read'])]
    private ?User $updatedBy = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['kb:read'])]
    private int $viewCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['kb:read'])]
    private int $helpfulCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['kb:read'])]
    private int $notHelpfulCount = 0;

    #[ORM\Column]
    #[Groups(['kb:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    #[Groups(['kb:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['kb:read'])]
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        if (!$this->slug) {
            $this->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper($status);
        if ($this->status === 'PUBLISHED' && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getCategory(): ?KnowledgeCategory
    {
        return $this->category;
    }

    public function setCategory(?KnowledgeCategory $category): self
    {
        $this->category = $category;
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

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): self
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function getHelpfulCount(): int
    {
        return $this->helpfulCount;
    }

    public function setHelpfulCount(int $helpfulCount): self
    {
        $this->helpfulCount = $helpfulCount;
        return $this;
    }

    public function getNotHelpfulCount(): int
    {
        return $this->notHelpfulCount;
    }

    public function setNotHelpfulCount(int $notHelpfulCount): self
    {
        $this->notHelpfulCount = $notHelpfulCount;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getHelpfulPercentage(): float
    {
        $total = $this->helpfulCount + $this->notHelpfulCount;
        if ($total === 0) return 100.0;
        return round(($this->helpfulCount / $total) * 100, 1);
    }
}
