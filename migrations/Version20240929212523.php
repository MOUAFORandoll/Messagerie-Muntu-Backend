<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240929212523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE canal_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE canal_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE conversation_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE groupe_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE groupe_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE message_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE message_object_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE message_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE type_object_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE type_participant_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE type_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_object_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE canal (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, description TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE canal_user (id INT NOT NULL, canal_id INT DEFAULT NULL, muntu_id INT DEFAULT NULL, type_user_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1340295468DB5B2E ON canal_user (canal_id)');
        $this->addSql('CREATE INDEX IDX_134029546BD1F126 ON canal_user (muntu_id)');
        $this->addSql('CREATE INDEX IDX_134029548F4FBC60 ON canal_user (type_user_id)');
        $this->addSql('CREATE TABLE conversation_user (id INT NOT NULL, first_id INT DEFAULT NULL, second_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5AECB555E84D625F ON conversation_user (first_id)');
        $this->addSql('CREATE INDEX IDX_5AECB555FF961BCC ON conversation_user (second_id)');
        $this->addSql('CREATE TABLE groupe (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE groupe_user (id INT NOT NULL, groupe_id INT DEFAULT NULL, muntu_id INT DEFAULT NULL, type_user_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_257BA9FE7A45358C ON groupe_user (groupe_id)');
        $this->addSql('CREATE INDEX IDX_257BA9FE6BD1F126 ON groupe_user (muntu_id)');
        $this->addSql('CREATE INDEX IDX_257BA9FE8F4FBC60 ON groupe_user (type_user_id)');
        $this->addSql('CREATE TABLE message (id INT NOT NULL, message_object_id INT DEFAULT NULL, emetteur_groupe_id INT DEFAULT NULL, emetteur_canal_id INT DEFAULT NULL, valeur TEXT NOT NULL, status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307F4994501D ON message (message_object_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FE4A2C99A ON message (emetteur_groupe_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FEBB2EE29 ON message (emetteur_canal_id)');
        $this->addSql('CREATE TABLE message_object (id INT NOT NULL, type_object_id INT DEFAULT NULL, message_user_id INT DEFAULT NULL, message_id INT DEFAULT NULL, src VARCHAR(2055) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9F8A36E397EB173B ON message_object (type_object_id)');
        $this->addSql('CREATE INDEX IDX_9F8A36E337E6E999 ON message_object (message_user_id)');
        $this->addSql('CREATE INDEX IDX_9F8A36E3537A1329 ON message_object (message_id)');
        $this->addSql('CREATE TABLE message_user (id INT NOT NULL, conversation_id INT DEFAULT NULL, emetteur_id INT DEFAULT NULL, valeur TEXT NOT NULL, status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_24064D909AC0396 ON message_user (conversation_id)');
        $this->addSql('CREATE INDEX IDX_24064D9079E92E8C ON message_user (emetteur_id)');
        $this->addSql('CREATE TABLE type_object (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE type_participant (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE type_user (id INT NOT NULL, libelle VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('CREATE TABLE user_object (id INT NOT NULL, user_plateform_id INT DEFAULT NULL, src VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CDFFB0D14C66282D ON user_object (user_plateform_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE canal_user ADD CONSTRAINT FK_1340295468DB5B2E FOREIGN KEY (canal_id) REFERENCES canal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE canal_user ADD CONSTRAINT FK_134029546BD1F126 FOREIGN KEY (muntu_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE canal_user ADD CONSTRAINT FK_134029548F4FBC60 FOREIGN KEY (type_user_id) REFERENCES type_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB555E84D625F FOREIGN KEY (first_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB555FF961BCC FOREIGN KEY (second_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_user ADD CONSTRAINT FK_257BA9FE7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_user ADD CONSTRAINT FK_257BA9FE6BD1F126 FOREIGN KEY (muntu_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groupe_user ADD CONSTRAINT FK_257BA9FE8F4FBC60 FOREIGN KEY (type_user_id) REFERENCES type_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F4994501D FOREIGN KEY (message_object_id) REFERENCES message_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE4A2C99A FOREIGN KEY (emetteur_groupe_id) REFERENCES groupe_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FEBB2EE29 FOREIGN KEY (emetteur_canal_id) REFERENCES canal_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_object ADD CONSTRAINT FK_9F8A36E397EB173B FOREIGN KEY (type_object_id) REFERENCES type_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_object ADD CONSTRAINT FK_9F8A36E337E6E999 FOREIGN KEY (message_user_id) REFERENCES message_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_object ADD CONSTRAINT FK_9F8A36E3537A1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_user ADD CONSTRAINT FK_24064D909AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_user ADD CONSTRAINT FK_24064D9079E92E8C FOREIGN KEY (emetteur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_object ADD CONSTRAINT FK_CDFFB0D14C66282D FOREIGN KEY (user_plateform_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE canal_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE canal_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE conversation_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE groupe_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE groupe_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE message_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE message_object_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE message_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE type_object_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE type_participant_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE type_user_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE user_object_id_seq CASCADE');
        $this->addSql('ALTER TABLE canal_user DROP CONSTRAINT FK_1340295468DB5B2E');
        $this->addSql('ALTER TABLE canal_user DROP CONSTRAINT FK_134029546BD1F126');
        $this->addSql('ALTER TABLE canal_user DROP CONSTRAINT FK_134029548F4FBC60');
        $this->addSql('ALTER TABLE conversation_user DROP CONSTRAINT FK_5AECB555E84D625F');
        $this->addSql('ALTER TABLE conversation_user DROP CONSTRAINT FK_5AECB555FF961BCC');
        $this->addSql('ALTER TABLE groupe_user DROP CONSTRAINT FK_257BA9FE7A45358C');
        $this->addSql('ALTER TABLE groupe_user DROP CONSTRAINT FK_257BA9FE6BD1F126');
        $this->addSql('ALTER TABLE groupe_user DROP CONSTRAINT FK_257BA9FE8F4FBC60');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F4994501D');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FE4A2C99A');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FEBB2EE29');
        $this->addSql('ALTER TABLE message_object DROP CONSTRAINT FK_9F8A36E397EB173B');
        $this->addSql('ALTER TABLE message_object DROP CONSTRAINT FK_9F8A36E337E6E999');
        $this->addSql('ALTER TABLE message_object DROP CONSTRAINT FK_9F8A36E3537A1329');
        $this->addSql('ALTER TABLE message_user DROP CONSTRAINT FK_24064D909AC0396');
        $this->addSql('ALTER TABLE message_user DROP CONSTRAINT FK_24064D9079E92E8C');
        $this->addSql('ALTER TABLE user_object DROP CONSTRAINT FK_CDFFB0D14C66282D');
        $this->addSql('DROP TABLE canal');
        $this->addSql('DROP TABLE canal_user');
        $this->addSql('DROP TABLE conversation_user');
        $this->addSql('DROP TABLE groupe');
        $this->addSql('DROP TABLE groupe_user');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_object');
        $this->addSql('DROP TABLE message_user');
        $this->addSql('DROP TABLE type_object');
        $this->addSql('DROP TABLE type_participant');
        $this->addSql('DROP TABLE type_user');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_object');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
