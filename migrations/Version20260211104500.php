<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les colonnes ENUM en VARCHAR pour les enums PHP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretien CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE candidature CHANGE statut_reponse statut_reponse VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE entretien CHANGE statut statut ENUM('prevu', 'passe') NOT NULL");
        $this->addSql("ALTER TABLE candidature CHANGE statut_reponse statut_reponse ENUM('attente', 'echanges', 'entretien', 'negative', 'engage', 'annule') NOT NULL");
    }
}
