<?php

namespace App\Entity;

use App\Repository\KnowledgeArticleFeedbackRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KnowledgeArticleFeedbackRepository::class)]
#[ORM\Table(name: 'knowledge_article_feedback')]
#[ORM\UniqueConstraint(name: 'unique_article_user_feedback', columns: ['article_id', 'user_id'])]
class KnowledgeArticleFeedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['kb:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: KnowledgeArticle::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?KnowledgeArticle $article = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['kb:read'])]
    private bool $helpful = true;

    #[ORM\Column]
    #[Groups(['kb:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?KnowledgeArticle
    {
        return $this->article;
    }

    public function setArticle(?KnowledgeArticle $article): self
    {
        $this->article = $article;
        return $this;
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

    public function isHelpful(): bool
    {
        return $this->helpful;
    }

    public function setHelpful(bool $helpful): self
    {
        $this->helpful = $helpful;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
