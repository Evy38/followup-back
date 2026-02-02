<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128130049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reponse_mot_cle DROP FOREIGN KEY FK_2695AD13CF18BB82');
        $this->addSql('ALTER TABLE reponse_mot_cle DROP FOREIGN KEY FK_2695AD13FE94535C');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7B6121583');
        $this->addSql('DROP TABLE reponse_mot_cle');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B868DB5B2E');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8A73F0036');
        $this->addSql('DROP INDEX IDX_E33BD3B8A73F0036 ON candidature');
        $this->addSql('DROP INDEX IDX_E33BD3B868DB5B2E ON candidature');
        $this->addSql('ALTER TABLE candidature ADD job_title VARCHAR(255) NOT NULL, DROP ville_id, DROP canal_id, DROP date_envoi');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reponse_mot_cle (reponse_id INT NOT NULL, mot_cle_id INT NOT NULL, INDEX IDX_2695AD13CF18BB82 (reponse_id), INDEX IDX_2695AD13FE94535C (mot_cle_id), PRIMARY KEY(reponse_id, mot_cle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, candidature_id INT NOT NULL, date_reponse DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', canal VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, commentaire LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_5FB6DEC7B6121583 (candidature_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reponse_mot_cle ADD CONSTRAINT FK_2695AD13CF18BB82 FOREIGN KEY (reponse_id) REFERENCES reponse (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse_mot_cle ADD CONSTRAINT FK_2695AD13FE94535C FOREIGN KEY (mot_cle_id) REFERENCES mot_cle (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE candidature ADD ville_id INT DEFAULT NULL, ADD canal_id INT DEFAULT NULL, ADD date_envoi DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP job_title');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B868DB5B2E FOREIGN KEY (canal_id) REFERENCES canal (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E33BD3B8A73F0036 ON candidature (ville_id)');
        $this->addSql('CREATE INDEX IDX_E33BD3B868DB5B2E ON candidature (canal_id)');
    }
}
