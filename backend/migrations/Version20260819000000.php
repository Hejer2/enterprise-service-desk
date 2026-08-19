<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create knowledge_categories, knowledge_articles, and knowledge_article_feedback tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS knowledge_categories (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            icon VARCHAR(50) DEFAULT \'📁\',
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_KB_CATEGORY_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS knowledge_articles (
            id INT AUTO_INCREMENT NOT NULL,
            category_id INT NOT NULL,
            created_by_id INT NOT NULL,
            updated_by_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt LONGTEXT DEFAULT NULL,
            status VARCHAR(20) DEFAULT \'DRAFT\' NOT NULL,
            view_count INT DEFAULT 0 NOT NULL,
            helpful_count INT DEFAULT 0 NOT NULL,
            not_helpful_count INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_KB_ARTICLE_SLUG (slug),
            INDEX idx_kb_status (status),
            INDEX idx_kb_category (category_id),
            INDEX idx_kb_published_at (published_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_KB_ARTICLE_CATEGORY FOREIGN KEY (category_id) REFERENCES knowledge_categories (id) ON DELETE RESTRICT,
            CONSTRAINT FK_KB_ARTICLE_CREATOR FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE RESTRICT,
            CONSTRAINT FK_KB_ARTICLE_UPDATER FOREIGN KEY (updated_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS knowledge_article_feedback (
            id INT AUTO_INCREMENT NOT NULL,
            article_id INT NOT NULL,
            user_id INT NOT NULL,
            helpful TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX unique_article_user_feedback (article_id, user_id),
            INDEX IDX_KB_FEEDBACK_ARTICLE (article_id),
            INDEX IDX_KB_FEEDBACK_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_KB_FEEDBACK_ARTICLE FOREIGN KEY (article_id) REFERENCES knowledge_articles (id) ON DELETE CASCADE,
            CONSTRAINT FK_KB_FEEDBACK_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS knowledge_article_feedback');
        $this->addSql('DROP TABLE IF EXISTS knowledge_articles');
        $this->addSql('DROP TABLE IF EXISTS knowledge_categories');
    }
}
