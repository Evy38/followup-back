<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionXXXXXXXXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace statutReponse "positive" par "engage" et resultat entretien "positive" par "engage"';
    }

    public function up(Schema $schema): void
    {
        // Migration des candidatures
        $this->addSql("
            UPDATE candidature 
            SET statut_reponse = 'engage' 
            WHERE statut_reponse = 'positive'
        ");

        // Migration des entretiens (si vous avez déjà des données)
        $this->addSql("
            UPDATE entretien 
            SET resultat = 'engage' 
            WHERE resultat = 'positive'
        ");
    }

    public function down(Schema $schema): void
    {
        // Rollback si nécessaire
        $this->addSql("
            UPDATE candidature 
            SET statut_reponse = 'positive' 
            WHERE statut_reponse = 'engage'
        ");

        $this->addSql("
            UPDATE entretien 
            SET resultat = 'positive' 
            WHERE resultat = 'engage'
        ");
    }
}