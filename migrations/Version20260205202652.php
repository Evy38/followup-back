<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour transformer les colonnes VARCHAR en ENUM natifs MySQL.
 */
final class Version20260205202652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transformation des statuts VARCHAR en ENUM natifs pour Entretien et Candidature';
    }

    public function up(Schema $schema): void
    {
        // Transformation du statut d'entretien en ENUM
        $this->addSql("ALTER TABLE entretien MODIFY statut ENUM('prevu', 'passe') NOT NULL DEFAULT 'prevu'");
        
        // Transformation du résultat d'entretien en ENUM
        $this->addSql("ALTER TABLE entretien MODIFY resultat ENUM('engage', 'negative', 'attente') DEFAULT NULL");
        
        // Transformation du statut de réponse de candidature en ENUM
        $this->addSql("ALTER TABLE candidature MODIFY statut_reponse ENUM('attente', 'echanges', 'entretien', 'negative', 'engage', 'annule') NOT NULL DEFAULT 'attente'");
    }

    public function down(Schema $schema): void
    {
        // Retour aux VARCHAR pour rollback
        $this->addSql("ALTER TABLE entretien MODIFY statut VARCHAR(20) NOT NULL");
        $this->addSql("ALTER TABLE entretien MODIFY resultat VARCHAR(20) DEFAULT NULL");
        $this->addSql("ALTER TABLE candidature MODIFY statut_reponse VARCHAR(20) NOT NULL DEFAULT 'attente'");
    }
}