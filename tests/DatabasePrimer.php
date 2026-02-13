<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * 🎓 TRAIT DatabasePrimer
 * 
 * Ce trait permet de préparer la base de données AVANT tous les tests d'une classe.
 * 
 * 📌 Usage :
 * ```php
 * use App\Tests\DatabasePrimer;
 * 
 * class MyApiTest extends WebTestCase
 * {
 *     use DatabasePrimer;
 *     
 *     // PAS besoin de setUp() !
 *     // La BDD est automatiquement préparée avant le premier test
 * }
 * ```
 * 
 * ⚠️ IMPORTANT : Ne PAS appeler resetDatabase() manuellement.
 * La méthode setUpBeforeClass() le fait automatiquement.
 */
trait DatabasePrimer
{
    /**
     * Appelé UNE SEULE FOIS avant TOUS les tests de la classe.
     * 
     * ✅ Avantage : Pas de conflit avec bootKernel() / createClient()
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // On boot le kernel AVANT que les tests individuels ne le fassent
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->setAutoExit(false);

        // 1️⃣ Supprimer la BDD si elle existe
        self::runCommand($application, 'doctrine:database:drop', ['--force' => true, '--if-exists' => true]);
        
        // 2️⃣ Créer la BDD
        self::runCommand($application, 'doctrine:database:create');
        
        // 3️⃣ Créer le schéma (toutes les tables)
        self::runCommand($application, 'doctrine:schema:create');
        
        // 4️⃣ Arrêter le kernel pour permettre à createClient() de le redémarrer
        self::ensureKernelShutdown();
    }

    /**
     * Exécute une commande Symfony en mode silencieux.
     */
    private static function runCommand(Application $application, string $command, array $options = []): void
    {
        $options = array_merge(['command' => $command], $options);
        $application->run(new ArrayInput($options), new NullOutput());
    }
}