<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206InitialSchema extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema aligned with entities (enums, relations, defaults)';
    }

    public function up(Schema $schema): void
    {
        // --- USER ---
        $this->addSql("
            CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                first_name VARCHAR(100) DEFAULT NULL,
                last_name VARCHAR(100) DEFAULT NULL,
                google_id VARCHAR(255) DEFAULT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) DEFAULT NULL,
                reset_password_token VARCHAR(100) DEFAULT NULL,
                reset_password_token_expires_at DATETIME DEFAULT NULL,
                is_verified TINYINT(1) NOT NULL DEFAULT 0,
                email_verification_token VARCHAR(100) DEFAULT NULL,
                email_verification_token_expires_at DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- ENTREPRISE ---
        $this->addSql("
            CREATE TABLE entreprise (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(100) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- STATUT ---
        $this->addSql("
            CREATE TABLE statut (
                id INT AUTO_INCREMENT NOT NULL,
                libelle VARCHAR(100) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- CANDIDATURE ---
        $this->addSql("
            CREATE TABLE candidature (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                entreprise_id INT NOT NULL,
                statut_id INT NOT NULL,
                date_candidature DATETIME NOT NULL,
                job_title VARCHAR(255) NOT NULL,
                lien_annonce VARCHAR(255) DEFAULT NULL,
                mode VARCHAR(50) DEFAULT NULL,
                external_offer_id VARCHAR(100) NOT NULL,
                date_derniere_relance DATETIME DEFAULT NULL,
                statut_reponse ENUM(
                    'attente',
                    'echanges',
                    'entretien',
                    'negative',
                    'engage',
                    'annule'
                ) NOT NULL DEFAULT 'attente',
                INDEX IDX_CANDIDATURE_USER (user_id),
                INDEX IDX_CANDIDATURE_ENTREPRISE (entreprise_id),
                INDEX IDX_CANDIDATURE_STATUT (statut_id),
                INDEX IDX_CANDIDATURE_EXTERNAL_OFFER (external_offer_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- ENTRETIEN ---
        $this->addSql("
            CREATE TABLE entretien (
                id INT AUTO_INCREMENT NOT NULL,
                candidature_id INT NOT NULL,
                date_entretien DATE NOT NULL,
                heure_entretien TIME DEFAULT NULL,
                statut ENUM('prevu','passe') NOT NULL DEFAULT 'prevu',
                resultat ENUM('engage','negative','attente') DEFAULT NULL,
                INDEX IDX_ENTRETIEN_CANDIDATURE (candidature_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- RELANCE ---
        $this->addSql("
            CREATE TABLE relance (
                id INT AUTO_INCREMENT NOT NULL,
                candidature_id INT NOT NULL,
                date_relance DATETIME NOT NULL,
                type VARCHAR(50) DEFAULT NULL,
                contenu LONGTEXT DEFAULT NULL,
                rang SMALLINT NOT NULL,
                faite TINYINT(1) NOT NULL DEFAULT 0,
                date_realisation DATETIME DEFAULT NULL,
                INDEX IDX_RELANCE_CANDIDATURE (candidature_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB
        ");

        // --- FK ---
        $this->addSql("ALTER TABLE candidature ADD CONSTRAINT FK_CANDIDATURE_USER FOREIGN KEY (user_id) REFERENCES user (id)");
        $this->addSql("ALTER TABLE candidature ADD CONSTRAINT FK_CANDIDATURE_ENTREPRISE FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)");
        $this->addSql("ALTER TABLE candidature ADD CONSTRAINT FK_CANDIDATURE_STATUT FOREIGN KEY (statut_id) REFERENCES statut (id)");
        $this->addSql("ALTER TABLE entretien ADD CONSTRAINT FK_ENTRETIEN_CANDIDATURE FOREIGN KEY (candidature_id) REFERENCES candidature (id)");
        $this->addSql("ALTER TABLE relance ADD CONSTRAINT FK_RELANCE_CANDIDATURE FOREIGN KEY (candidature_id) REFERENCES candidature (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE relance");
        $this->addSql("DROP TABLE entretien");
        $this->addSql("DROP TABLE candidature");
        $this->addSql("DROP TABLE statut");
        $this->addSql("DROP TABLE entreprise");
        $this->addSql("DROP TABLE user");
    }
}
