<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241103105316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow RENAME COLUMN suname_contact TO surname_contact');
        $this->addSql('ALTER TABLE user_object DROP CONSTRAINT fk_cdffb0d14c66282d');
        $this->addSql('DROP INDEX idx_cdffb0d14c66282d');
        $this->addSql('ALTER TABLE user_object RENAME COLUMN user_plateform_id TO user_id');
        $this->addSql('ALTER TABLE user_object ADD CONSTRAINT FK_CDFFB0D1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CDFFB0D1A76ED395 ON user_object (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE follow RENAME COLUMN surname_contact TO suname_contact');
        $this->addSql('ALTER TABLE user_object DROP CONSTRAINT FK_CDFFB0D1A76ED395');
        $this->addSql('DROP INDEX IDX_CDFFB0D1A76ED395');
        $this->addSql('ALTER TABLE user_object RENAME COLUMN user_id TO user_plateform_id');
        $this->addSql('ALTER TABLE user_object ADD CONSTRAINT fk_cdffb0d14c66282d FOREIGN KEY (user_plateform_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_cdffb0d14c66282d ON user_object (user_plateform_id)');
    }
}
