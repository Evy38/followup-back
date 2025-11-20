<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116212912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE site_web site_web VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_AF92D22AA4D60759 ON mot_cle');
        $this->addSql('ALTER TABLE mot_cle CHANGE libelle libelle VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE statut CHANGE libelle libelle VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(100) DEFAULT NULL, ADD last_name VARCHAR(100) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ville CHANGE code_postal code_postal VARCHAR(10) DEFAULT NULL, CHANGE pays pays VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mot_cle CHANGE libelle libelle VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF92D22AA4D60759 ON mot_cle (libelle)');
        $this->addSql('ALTER TABLE user DROP first_name, DROP last_name, DROP google_id, CHANGE password password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE entreprise CHANGE nom nom VARCHAR(150) NOT NULL, CHANGE site_web site_web VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE statut CHANGE libelle libelle VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE ville CHANGE code_postal code_postal VARCHAR(20) DEFAULT NULL, CHANGE pays pays VARCHAR(100) NOT NULL');
    }
}
