<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202145321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Tables de jointure (toujours en premier)
        $this->addSql('DROP TABLE relance_mot_cle');
        $this->addSql('DROP TABLE candidature_mot_cle');

        // 2. Tables métier supprimées
        $this->addSql('DROP TABLE mot_cle');
        $this->addSql('DROP TABLE canal');
        $this->addSql('DROP TABLE ville');

        // 3. Nettoyage entreprise
        $this->addSql('ALTER TABLE entreprise DROP secteur, DROP site_web');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE canal (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ville (id INT AUTO_INCREMENT NOT NULL, nom_ville VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, code_postal VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, pays VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mot_cle (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE relance_mot_cle (relance_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_C6B7E0C4FE94535C (mot_cle_id), INDEX IDX_C6B7E0C4D4D37339 (relance_id), PRIMARY KEY(relance_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE candidature_mot_cle (candidature_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_452BADFCFE94535C (mot_cle_id), INDEX IDX_452BADFCB6121583 (candidature_id), PRIMARY KEY(candidature_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE relance_mot_cle ADD CONSTRAINT FK_C6B7E0C4D4D37339 FOREIGN KEY (relance_id) REFERENCES relance (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relance_mot_cle ADD CONSTRAINT FK_C6B7E0C4FE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_mot_cle ADD CONSTRAINT FK_452BADFCB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_mot_cle ADD CONSTRAINT FK_452BADFCFE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise ADD secteur VARCHAR(100) DEFAULT NULL, ADD site_web VARCHAR(255) DEFAULT NULL');
    }
}
