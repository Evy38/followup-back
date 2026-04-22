<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419192407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make candidature.external_offer_id nullable to support manual candidatures';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidature ALTER COLUMN external_offer_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE candidature SET external_offer_id = '' WHERE external_offer_id IS NULL");
        $this->addSql('ALTER TABLE candidature ALTER COLUMN external_offer_id SET NOT NULL');
    }
}
