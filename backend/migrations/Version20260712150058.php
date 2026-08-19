<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260712150058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD COLUMN plain_password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, password, first_name, last_name, phone, profile_picture, language, theme, fcm_token, created_at, updated_at, role_entity_id FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(30) DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, language VARCHAR(5) DEFAULT \'en\' NOT NULL, theme VARCHAR(10) DEFAULT \'light\' NOT NULL, fcm_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, role_entity_id INTEGER NOT NULL, CONSTRAINT FK_1483A5E9D0D1AE81 FOREIGN KEY (role_entity_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO users (id, email, password, first_name, last_name, phone, profile_picture, language, theme, fcm_token, created_at, updated_at, role_entity_id) SELECT id, email, password, first_name, last_name, phone, profile_picture, language, theme, fcm_token, created_at, updated_at, role_entity_id FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9D0D1AE81 ON users (role_entity_id)');
    }
}
