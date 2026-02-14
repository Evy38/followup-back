<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214231525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidature (id SERIAL NOT NULL, user_id INT NOT NULL, entreprise_id INT NOT NULL, statut_id INT NOT NULL, date_candidature TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, job_title VARCHAR(255) NOT NULL, lien_annonce VARCHAR(255) DEFAULT NULL, mode VARCHAR(50) DEFAULT NULL, external_offer_id VARCHAR(100) NOT NULL, date_derniere_relance TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, statut_reponse VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E33BD3B8A76ED395 ON candidature (user_id)');
        $this->addSql('CREATE INDEX IDX_E33BD3B8A4AEAFEA ON candidature (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_E33BD3B8F6203804 ON candidature (statut_id)');
        $this->addSql('COMMENT ON COLUMN candidature.date_candidature IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN candidature.date_derniere_relance IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE entreprise (id SERIAL NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE entretien (id SERIAL NOT NULL, candidature_id INT NOT NULL, date_entretien DATE NOT NULL, heure_entretien TIME(0) WITHOUT TIME ZONE DEFAULT NULL, statut VARCHAR(255) NOT NULL, resultat VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2B58D6DAB6121583 ON entretien (candidature_id)');
        $this->addSql('CREATE TABLE relance (id SERIAL NOT NULL, candidature_id INT NOT NULL, date_relance TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, type VARCHAR(50) DEFAULT NULL, contenu TEXT DEFAULT NULL, rang SMALLINT NOT NULL, faite BOOLEAN DEFAULT false NOT NULL, date_realisation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_50BBC126B6121583 ON relance (candidature_id)');
        $this->addSql('COMMENT ON COLUMN relance.date_relance IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN relance.date_realisation IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE statut (id SERIAL NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, pending_email VARCHAR(180) DEFAULT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, reset_password_token VARCHAR(100) DEFAULT NULL, reset_password_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_verified BOOLEAN NOT NULL, email_verification_token VARCHAR(100) DEFAULT NULL, email_verification_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, consent_rgpd BOOLEAN NOT NULL, consent_rgpd_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deletion_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".reset_password_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".email_verification_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".consent_rgpd_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".deletion_requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8F6203804 FOREIGN KEY (statut_id) REFERENCES statut (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_2B58D6DAB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT FK_E33BD3B8A76ED395');
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT FK_E33BD3B8A4AEAFEA');
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT FK_E33BD3B8F6203804');
        $this->addSql('ALTER TABLE entretien DROP CONSTRAINT FK_2B58D6DAB6121583');
        $this->addSql('ALTER TABLE relance DROP CONSTRAINT FK_50BBC126B6121583');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE entretien');
        $this->addSql('DROP TABLE relance');
        $this->addSql('DROP TABLE statut');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
