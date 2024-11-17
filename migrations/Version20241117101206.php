<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241117101206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_user ADD message_target_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_user ADD CONSTRAINT FK_24064D907F370D50 FOREIGN KEY (message_target_id) REFERENCES message_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_24064D907F370D50 ON message_user (message_target_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE message_user DROP CONSTRAINT FK_24064D907F370D50');
        $this->addSql('DROP INDEX IDX_24064D907F370D50');
        $this->addSql('ALTER TABLE message_user DROP message_target_id');
    }
}
