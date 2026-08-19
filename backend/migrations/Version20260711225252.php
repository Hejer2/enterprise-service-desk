<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260711225252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_logs (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "action" VARCHAR(100) NOT NULL, entity_name VARCHAR(100) DEFAULT NULL, entity_id INTEGER DEFAULT NULL, details CLOB DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_F34B1DCEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F34B1DCEA76ED395 ON activity_logs (user_id)');
        $this->addSql('CREATE TABLE departments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(10) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16AEB8D45E237E06 ON departments (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16AEB8D477153098 ON departments (code)');
        $this->addSql('CREATE TABLE leave_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, half_day BOOLEAN DEFAULT 0 NOT NULL, ticket_id INTEGER NOT NULL, leave_type_id INTEGER NOT NULL, CONSTRAINT FK_45ADFEF2700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_45ADFEF28313F474 FOREIGN KEY (leave_type_id) REFERENCES leave_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_45ADFEF2700047D2 ON leave_requests (ticket_id)');
        $this->addSql('CREATE INDEX IDX_45ADFEF28313F474 ON leave_requests (leave_type_id)');
        $this->addSql('CREATE TABLE leave_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(20) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9CE4D09A77153098 ON leave_types (code)');
        $this->addSql('CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content CLOB NOT NULL, is_read BOOLEAN DEFAULT 0 NOT NULL, type VARCHAR(50) NOT NULL, related_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
        $this->addSql('CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DEDCC6F5E237E06 ON permissions (name)');
        $this->addSql('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, display_name VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name)');
        $this->addSql('CREATE TABLE role_permissions (role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, PRIMARY KEY (role_id, permission_id), CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6FED90CCA ON role_permissions (permission_id)');
        $this->addSql('CREATE TABLE settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, setting_key VARCHAR(100) NOT NULL, setting_value CLOB DEFAULT NULL, category VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E545A0C55FA1E697 ON settings (setting_key)');
        $this->addSql('CREATE TABLE ticket_attachments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, file_type VARCHAR(100) NOT NULL, file_size INTEGER NOT NULL, created_at DATETIME NOT NULL, ticket_id INTEGER DEFAULT NULL, message_id INTEGER DEFAULT NULL, uploaded_by_id INTEGER NOT NULL, CONSTRAINT FK_2B54FCA9700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2B54FCA9537A1329 FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2B54FCA9A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2B54FCA9700047D2 ON ticket_attachments (ticket_id)');
        $this->addSql('CREATE INDEX IDX_2B54FCA9537A1329 ON ticket_attachments (message_id)');
        $this->addSql('CREATE INDEX IDX_2B54FCA9A2B28FE8 ON ticket_attachments (uploaded_by_id)');
        $this->addSql('CREATE TABLE ticket_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, message CLOB NOT NULL, is_edited BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ticket_id INTEGER NOT NULL, sender_id INTEGER NOT NULL, CONSTRAINT FK_5E6BE217700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5E6BE217F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5E6BE217700047D2 ON ticket_messages (ticket_id)');
        $this->addSql('CREATE INDEX IDX_5E6BE217F624B39D ON ticket_messages (sender_id)');
        $this->addSql('CREATE TABLE tickets (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ticket_number VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB NOT NULL, category VARCHAR(50) NOT NULL, priority VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL, due_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, department_id INTEGER DEFAULT NULL, created_by_id INTEGER NOT NULL, assigned_to_id INTEGER DEFAULT NULL, CONSTRAINT FK_54469DF4AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54469DF4B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54469DF4F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54469DF4ECD2759F ON tickets (ticket_number)');
        $this->addSql('CREATE INDEX IDX_54469DF4AE80F5DF ON tickets (department_id)');
        $this->addSql('CREATE INDEX IDX_54469DF4B03A8386 ON tickets (created_by_id)');
        $this->addSql('CREATE INDEX IDX_54469DF4F4BD7827 ON tickets (assigned_to_id)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(30) DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, language VARCHAR(5) DEFAULT \'en\' NOT NULL, theme VARCHAR(10) DEFAULT \'light\' NOT NULL, fcm_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, department_id INTEGER DEFAULT NULL, role_entity_id INTEGER NOT NULL, CONSTRAINT FK_1483A5E9AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1483A5E9D0D1AE81 FOREIGN KEY (role_entity_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9AE80F5DF ON users (department_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9D0D1AE81 ON users (role_entity_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE activity_logs');
        $this->addSql('DROP TABLE departments');
        $this->addSql('DROP TABLE leave_requests');
        $this->addSql('DROP TABLE leave_types');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE ticket_attachments');
        $this->addSql('DROP TABLE ticket_messages');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
