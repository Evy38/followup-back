<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020101134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE canal (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, entreprise_id INT NOT NULL, ville_id INT DEFAULT NULL, canal_id INT DEFAULT NULL, statut_id INT NOT NULL, date_candidature DATE NOT NULL, mode VARCHAR(50) DEFAULT NULL, lien_annonce VARCHAR(255) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, INDEX IDX_E33BD3B8A76ED395 (user_id), INDEX IDX_E33BD3B8A4AEAFEA (entreprise_id), INDEX IDX_E33BD3B8A73F0036 (ville_id), INDEX IDX_E33BD3B868DB5B2E (canal_id), INDEX IDX_E33BD3B8F6203804 (statut_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candidature_mot_cle (candidature_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_452BADFCB6121583 (candidature_id), INDEX IDX_452BADFCFE94535C (mot_cle_id), PRIMARY KEY(candidature_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, secteur VARCHAR(100) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mot_cle (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE relance (id INT AUTO_INCREMENT NOT NULL, candidature_id INT NOT NULL, date_relance DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(50) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, INDEX IDX_50BBC126B6121583 (candidature_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE relance_mot_cle (relance_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_C6B7E0C4D4D37339 (relance_id), INDEX IDX_C6B7E0C4FE94535C (mot_cle_id), PRIMARY KEY(relance_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, candidature_id INT NOT NULL, date_reponse DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', canal VARCHAR(50) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, INDEX IDX_5FB6DEC7B6121583 (candidature_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reponse_mot_cle (reponse_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_2695AD13CF18BB82 (reponse_id), INDEX IDX_2695AD13FE94535C (mot_cle_id), PRIMARY KEY(reponse_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statut (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ville (id INT AUTO_INCREMENT NOT NULL, nom_ville VARCHAR(100) NOT NULL, code_postal VARCHAR(10) DEFAULT NULL, pays VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B868DB5B2E FOREIGN KEY (canal_id) REFERENCES canal (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8F6203804 FOREIGN KEY (statut_id) REFERENCES statut (id)');
        $this->addSql('ALTER TABLE candidature_mot_cle ADD CONSTRAINT FK_452BADFCB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_mot_cle ADD CONSTRAINT FK_452BADFCFE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('ALTER TABLE relance_mot_cle ADD CONSTRAINT FK_C6B7E0C4D4D37339 FOREIGN KEY (relance_id) REFERENCES relance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relance_mot_cle ADD CONSTRAINT FK_C6B7E0C4FE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('ALTER TABLE reponse_mot_cle ADD CONSTRAINT FK_2695AD13CF18BB82 FOREIGN KEY (reponse_id) REFERENCES reponse (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse_mot_cle ADD CONSTRAINT FK_2695AD13FE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A76ED395');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A4AEAFEA');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A73F0036');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B868DB5B2E');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8F6203804');
        $this->addSql('ALTER TABLE candidature_mot_cle DROP FOREIGN KEY FK_452BADFCB6121583');
        $this->addSql('ALTER TABLE candidature_mot_cle DROP FOREIGN KEY FK_452BADFCFE94535C');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126B6121583');
        $this->addSql('ALTER TABLE relance_mot_cle DROP FOREIGN KEY FK_C6B7E0C4D4D37339');
        $this->addSql('ALTER TABLE relance_mot_cle DROP FOREIGN KEY FK_C6B7E0C4FE94535C');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7B6121583');
        $this->addSql('ALTER TABLE reponse_mot_cle DROP FOREIGN KEY FK_2695AD13CF18BB82');
        $this->addSql('ALTER TABLE reponse_mot_cle DROP FOREIGN KEY FK_2695AD13FE94535C');
        $this->addSql('DROP TABLE canal');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE candidature_mot_cle');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE mot_cle');
        $this->addSql('DROP TABLE relance');
        $this->addSql('DROP TABLE relance_mot_cle');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE reponse_mot_cle');
        $this->addSql('DROP TABLE statut');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE ville');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
