<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412132202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT fk_e33bd3b8f6203804');
        $this->addSql('DROP TABLE statut');
        $this->addSql('DROP INDEX idx_e33bd3b8f6203804');
        $this->addSql('ALTER TABLE candidature DROP statut_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE statut (id UUID NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN statut.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE candidature ADD statut_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN candidature.statut_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT fk_e33bd3b8f6203804 FOREIGN KEY (statut_id) REFERENCES statut (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e33bd3b8f6203804 ON candidature (statut_id)');
    }
}
