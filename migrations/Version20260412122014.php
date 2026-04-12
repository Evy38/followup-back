<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412122014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archivedAt column to candidature table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ADD archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN candidature.archived_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature DROP COLUMN archived_at');
    }
}
