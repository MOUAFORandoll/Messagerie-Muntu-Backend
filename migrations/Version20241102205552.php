<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241102205552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE follow ADD name_contact VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE follow ADD suname_contact VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD phone VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD code_phone VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD surname VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP phone');
        $this->addSql('ALTER TABLE "user" DROP code_phone');
        $this->addSql('ALTER TABLE "user" DROP surname');
        $this->addSql('ALTER TABLE follow DROP name_contact');
        $this->addSql('ALTER TABLE follow DROP suname_contact');
    }
}
