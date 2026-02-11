<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionXXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema with proper ENUM types';
    }

    public function up(Schema $schema): void
    {
        // Candidature
        $this->addSql('CREATE TABLE candidature (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            entreprise_id INT NOT NULL,
            statut_id INT NOT NULL,
            date_candidature DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            job_title VARCHAR(255) NOT NULL,
            lien_annonce VARCHAR(255) DEFAULT NULL,
            mode VARCHAR(50) DEFAULT NULL,
            external_offer_id VARCHAR(100) NOT NULL,
            date_derniere_relance DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            statut_reponse ENUM(\'attente\', \'echanges\', \'entretien\', \'negative\', \'engage\', \'annule\') NOT NULL DEFAULT \'attente\',
            INDEX IDX_E33BD3B8A76ED395 (user_id),
            INDEX IDX_E33BD3B8A4AEAFEA (entreprise_id),
            INDEX IDX_E33BD3B8F6203804 (statut_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Entreprise
        $this->addSql('CREATE TABLE entreprise (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Entretien
        $this->addSql('CREATE TABLE entretien (
            id INT AUTO_INCREMENT NOT NULL,
            candidature_id INT NOT NULL,
            date_entretien DATE NOT NULL,
            heure_entretien TIME DEFAULT NULL,
            statut ENUM(\'prevu\', \'passe\') NOT NULL DEFAULT \'prevu\',
            resultat ENUM(\'engage\', \'negative\', \'attente\') DEFAULT NULL,
            INDEX IDX_2B58D6DAB6121583 (candidature_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Relance
        $this->addSql('CREATE TABLE relance (
            id INT AUTO_INCREMENT NOT NULL,
            candidature_id INT NOT NULL,
            date_relance DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            type VARCHAR(50) DEFAULT NULL,
            contenu LONGTEXT DEFAULT NULL,
            rang SMALLINT NOT NULL,
            faite TINYINT(1) DEFAULT 0 NOT NULL,
            date_realisation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_50BBC126B6121583 (candidature_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Statut
        $this->addSql('CREATE TABLE statut (
            id INT AUTO_INCREMENT NOT NULL,
            libelle VARCHAR(100) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // User
        $this->addSql('CREATE TABLE user (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            google_id VARCHAR(255) DEFAULT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) DEFAULT NULL,
            reset_password_token VARCHAR(100) DEFAULT NULL,
            reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_verified TINYINT(1) NOT NULL,
            email_verification_token VARCHAR(100) DEFAULT NULL,
            email_verification_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            consent_rgpd TINYINT(1) NOT NULL,
            consent_rgpd_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            deletion_requested TINYINT(1) NOT NULL,
            deletion_requested_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Messenger
        $this->addSql('CREATE TABLE messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_75EA56E0FB7336F0 (queue_name),
            INDEX IDX_75EA56E0E3BD61CE (available_at),
            INDEX IDX_75EA56E016BA31DB (delivered_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8F6203804 FOREIGN KEY (statut_id) REFERENCES statut (id)');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_2B58D6DAB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A76ED395');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A4AEAFEA');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8F6203804');
        $this->addSql('ALTER TABLE entretien DROP FOREIGN KEY FK_2B58D6DAB6121583');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126B6121583');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE entretien');
        $this->addSql('DROP TABLE relance');
        $this->addSql('DROP TABLE statut');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}