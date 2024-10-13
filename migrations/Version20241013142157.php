<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241013142157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE follow_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE jwt_refresh_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE follow (id INT NOT NULL, follower_id INT DEFAULT NULL, following_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_68344470AC24F853 ON follow (follower_id)');
        $this->addSql('CREATE INDEX IDX_683444701816E3A3 ON follow (following_id)');
        $this->addSql('CREATE TABLE jwt_refresh_token (id INT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_expire_token TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9F3D9535C74F2195 ON jwt_refresh_token (refresh_token)');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_68344470AC24F853 FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE follow ADD CONSTRAINT FK_683444701816E3A3 FOREIGN KEY (following_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD is_social_google BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_social_facebook BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE follow_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE jwt_refresh_token_id_seq CASCADE');
        $this->addSql('ALTER TABLE follow DROP CONSTRAINT FK_68344470AC24F853');
        $this->addSql('ALTER TABLE follow DROP CONSTRAINT FK_683444701816E3A3');
        $this->addSql('DROP TABLE follow');
        $this->addSql('DROP TABLE jwt_refresh_token');
        $this->addSql('ALTER TABLE "user" DROP is_social_google');
        $this->addSql('ALTER TABLE "user" DROP is_social_facebook');
        $this->addSql('ALTER TABLE "user" DROP email');
    }
}
