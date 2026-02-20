<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert required statut seed data';
    }

    public function up(Schema $schema): void
    {
        $statuts = ['Envoyée', 'En cours', 'Relancée', 'Entretien', 'Refusée', 'Acceptée'];

        foreach ($statuts as $libelle) {
            $this->addSql(
                "INSERT INTO statut (libelle) SELECT :libelle WHERE NOT EXISTS (SELECT 1 FROM statut WHERE libelle = :libelle)",
                ['libelle' => $libelle]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $statuts = ['Envoyée', 'En cours', 'Relancée', 'Entretien', 'Refusée', 'Acceptée'];

        foreach ($statuts as $libelle) {
            $this->addSql("DELETE FROM statut WHERE libelle = :libelle", ['libelle' => $libelle]);
        }
    }
}
