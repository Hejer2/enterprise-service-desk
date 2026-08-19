<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 2B Schema Migration: Creates csat_ratings and canned_responses tables.
 */
final class Version20260817150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create csat_ratings and canned_responses tables for Phase 2B.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE csat_ratings (
            id INT AUTO_INCREMENT NOT NULL,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            rating SMALLINT NOT NULL,
            comment LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CSAT_TICKET (ticket_id),
            INDEX IDX_CSAT_USER (user_id),
            UNIQUE INDEX unique_ticket_user_csat (ticket_id, user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE canned_responses (
            id INT AUTO_INCREMENT NOT NULL,
            created_by_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            category VARCHAR(50) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CANNED_CREATED_BY (created_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE csat_ratings ADD CONSTRAINT FK_CSAT_TICKET FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE csat_ratings ADD CONSTRAINT FK_CSAT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE canned_responses ADD CONSTRAINT FK_CANNED_USER FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE csat_ratings DROP FOREIGN KEY FK_CSAT_TICKET');
        $this->addSql('ALTER TABLE csat_ratings DROP FOREIGN KEY FK_CSAT_USER');
        $this->addSql('ALTER TABLE canned_responses DROP FOREIGN KEY FK_CANNED_USER');
        $this->addSql('DROP TABLE csat_ratings');
        $this->addSql('DROP TABLE canned_responses');
    }
}
