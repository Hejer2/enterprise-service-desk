<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_notifications and notification_preferences tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS user_notifications (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            entity_type VARCHAR(50) DEFAULT NULL,
            entity_id INT DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_un_user (user_id),
            INDEX idx_un_is_read (is_read),
            INDEX idx_un_created_at (created_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_UN_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS notification_preferences (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            ticket_assignments TINYINT(1) DEFAULT 1 NOT NULL,
            ticket_replies TINYINT(1) DEFAULT 1 NOT NULL,
            ticket_status_changes TINYINT(1) DEFAULT 1 NOT NULL,
            sla_alerts TINYINT(1) DEFAULT 1 NOT NULL,
            system_notifications TINYINT(1) DEFAULT 1 NOT NULL,
            browser_notifications TINYINT(1) DEFAULT 1 NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_NP_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_NP_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notification_preferences');
        $this->addSql('DROP TABLE IF EXISTS user_notifications');
    }
}
