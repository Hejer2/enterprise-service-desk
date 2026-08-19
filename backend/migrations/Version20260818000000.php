<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sla_policies and ticket_slas tables for Phase 3A Enterprise SLA Engine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS sla_policies (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            priority VARCHAR(20) NOT NULL,
            first_response_minutes INT NOT NULL,
            resolution_minutes INT NOT NULL,
            warning_percentage INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS ticket_slas (
            id INT AUTO_INCREMENT NOT NULL,
            ticket_id INT NOT NULL,
            sla_policy_id INT NOT NULL,
            first_response_due_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            resolution_due_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            first_response_completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            resolution_completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            first_response_status VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL,
            resolution_status VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL,
            warning_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            breached_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            paused_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            total_paused_minutes INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_TICKET_SLA (ticket_id),
            INDEX IDX_SLA_POLICY (sla_policy_id),
            INDEX IDX_SLA_RESOLUTION_STATUS (resolution_status),
            INDEX IDX_SLA_RESOLUTION_DUE (resolution_due_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_TICKET_SLA_TICKET FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
            CONSTRAINT FK_TICKET_SLA_POLICY FOREIGN KEY (sla_policy_id) REFERENCES sla_policies (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ticket_slas');
        $this->addSql('DROP TABLE IF EXISTS sla_policies');
    }
}
