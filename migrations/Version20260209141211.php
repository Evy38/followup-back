<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209141211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user 
         ADD consent_rgpd TINYINT(1) NOT NULL,
         ADD consent_rgpd_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
    }


    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user 
         DROP consent_rgpd,
         DROP consent_rgpd_at'
        );
    }

}
