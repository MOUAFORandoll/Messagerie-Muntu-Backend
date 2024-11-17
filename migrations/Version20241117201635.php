<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241117201635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow DROP CONSTRAINT fk_68344470a76ed395');
        $this->addSql('DROP INDEX idx_68344470a76ed395');
        $this->addSql('ALTER TABLE follow RENAME COLUMN user_id TO current_user_id');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_68344470D635610 FOREIGN KEY (current_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_68344470D635610 ON follow (current_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE follow DROP CONSTRAINT FK_68344470D635610');
        $this->addSql('DROP INDEX IDX_68344470D635610');
        $this->addSql('ALTER TABLE follow RENAME COLUMN current_user_id TO user_id');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT fk_68344470a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_68344470a76ed395 ON follow (user_id)');
    }
}
