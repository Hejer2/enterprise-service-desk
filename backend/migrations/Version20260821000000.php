<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create automation_rules, automation_executions, ticket_dependencies, recurring_tickets, and approval_requests tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS automation_rules (
            id INT AUTO_INCREMENT NOT NULL,
            created_by_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            trigger_type VARCHAR(50) NOT NULL,
            conditions JSON NOT NULL,
            actions JSON NOT NULL,
            priority INT DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_ar_is_active (is_active),
            INDEX idx_ar_trigger_type (trigger_type),
            PRIMARY KEY(id),
            CONSTRAINT FK_AR_CREATOR FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS automation_executions (
            id INT AUTO_INCREMENT NOT NULL,
            rule_id INT NOT NULL,
            ticket_id INT DEFAULT NULL,
            status VARCHAR(20) DEFAULT \'SUCCESS\' NOT NULL,
            actions_executed JSON NOT NULL,
            error_message LONGTEXT DEFAULT NULL,
            executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            execution_key VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_AE_KEY (execution_key),
            INDEX idx_ae_rule (rule_id),
            INDEX idx_ae_ticket (ticket_id),
            INDEX idx_ae_status (status),
            PRIMARY KEY(id),
            CONSTRAINT FK_AE_RULE FOREIGN KEY (rule_id) REFERENCES automation_rules (id) ON DELETE CASCADE,
            CONSTRAINT FK_AE_TICKET FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS ticket_dependencies (
            id INT AUTO_INCREMENT NOT NULL,
            ticket_id INT NOT NULL,
            depends_on_ticket_id INT NOT NULL,
            created_by_id INT DEFAULT NULL,
            dependency_type VARCHAR(30) DEFAULT \'BLOCKED_BY\' NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX unique_ticket_dependency (ticket_id, depends_on_ticket_id, dependency_type),
            INDEX IDX_TD_TICKET (ticket_id),
            INDEX IDX_TD_DEPENDS_ON (depends_on_ticket_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_TD_TICKET FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
            CONSTRAINT FK_TD_DEPENDS_ON FOREIGN KEY (depends_on_ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
            CONSTRAINT FK_TD_CREATOR FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS recurring_tickets (
            id INT AUTO_INCREMENT NOT NULL,
            assigned_to_id INT DEFAULT NULL,
            created_by_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            category VARCHAR(100) NOT NULL,
            priority VARCHAR(50) DEFAULT \'Medium\' NOT NULL,
            frequency VARCHAR(20) DEFAULT \'MONTHLY\' NOT NULL,
            next_run_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_rt_is_active (is_active),
            INDEX idx_rt_next_run_at (next_run_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_RT_ASSIGNEE FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON DELETE SET NULL,
            CONSTRAINT FK_RT_CREATOR FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS approval_requests (
            id INT AUTO_INCREMENT NOT NULL,
            ticket_id INT NOT NULL,
            requested_by_id INT NOT NULL,
            approver_id INT DEFAULT NULL,
            status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL,
            reason LONGTEXT DEFAULT NULL,
            comment LONGTEXT DEFAULT NULL,
            requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            responded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_ar_ticket (ticket_id),
            INDEX idx_ar_status (status),
            INDEX idx_ar_approver (approver_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_APPR_TICKET FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
            CONSTRAINT FK_APPR_REQUESTER FOREIGN KEY (requested_by_id) REFERENCES users (id) ON DELETE RESTRICT,
            CONSTRAINT FK_APPR_APPROVER FOREIGN KEY (approver_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS approval_requests');
        $this->addSql('DROP TABLE IF EXISTS recurring_tickets');
        $this->addSql('DROP TABLE IF EXISTS ticket_dependencies');
        $this->addSql('DROP TABLE IF EXISTS automation_executions');
        $this->addSql('DROP TABLE IF EXISTS automation_rules');
    }
}
