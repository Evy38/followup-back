<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260128100110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration historique (fichier restauré)';
    }

    public function up(Schema $schema): void
    {
        // Vide car déjà exécutée en base
    }

    public function down(Schema $schema): void
    {
        // Vide
    }
}