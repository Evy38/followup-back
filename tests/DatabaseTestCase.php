<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * 🧱 Classe de base pour tous les tests nécessitant une base de données.
 * 
 * Chaque test démarre avec :
 *  - le kernel Symfony chargé (donc les services disponibles)
 *  - la base `followup_test` complètement recréée à vide
 * 
 * 👉 Tes futurs tests pourront simplement étendre cette classe :
 *     class UserRepositoryTest extends DatabaseTestCase { ... }
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    /**
     * Méthode exécutée avant CHAQUE test.
     */
    protected function setUp(): void
    {
        // 1️⃣ Démarre le kernel Symfony (équivaut à un mini "symfony serve")
        self::bootKernel();

        // 2️⃣ Récupère le service Doctrine
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // 3️⃣ (Optionnel) Réinitialise la base pour chaque test
        $this->resetDatabase();
    }

    /**
     * 🧹 Ferme proprement l’EntityManager après chaque test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
        }

        $this->entityManager = null;
    }

    /**
     * ⚙️ Supprime puis recrée le schéma de base (toutes les tables)
     * à partir des métadonnées Doctrine.
     */
    private function resetDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Drop toute la base (plus sûr que dropSchema)
        $conn = $this->entityManager->getConnection();
        $platform = $conn->getDatabasePlatform();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        // Supprime messenger_messages si elle existe
        try {
            $conn->executeStatement($platform->getDropTableSQL('messenger_messages'));
        } catch (\Exception $e) {}
        foreach ($metadata as $meta) {
            $tableName = $meta->getTableName();
            try {
                $conn->executeStatement($platform->getDropTableSQL($tableName));
            } catch (\Exception $e) {
                // Ignore si la table n'existe pas
            }
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }
    }
}
