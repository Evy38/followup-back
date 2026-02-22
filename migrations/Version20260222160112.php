<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222160112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT FK_E33BD3B8A76ED395');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entretien DROP CONSTRAINT FK_2B58D6DAB6121583');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_2B58D6DAB6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE relance DROP CONSTRAINT FK_50BBC126B6121583');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE relance DROP CONSTRAINT fk_50bbc126b6121583');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT fk_50bbc126b6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entretien DROP CONSTRAINT fk_2b58d6dab6121583');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT fk_2b58d6dab6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidature DROP CONSTRAINT fk_e33bd3b8a76ed395');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT fk_e33bd3b8a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
