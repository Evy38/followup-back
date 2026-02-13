<?php

namespace App\Tests\Api;

use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Classe de base pour tous les tests API
 * 
 * Gère l'amorçage du kernel et la réinitialisation de la BDD correctement
 * 
 * INSIGHT: En Symfony WebTestCase, le problème "Booting the kernel before calling createClient()"
 * vient d'un appel explicite à bootKernel(). La solution est simple: ne pas appeler bootKernel().
 * À la place, utiliser getContainer() qui charge le kernel implicitement.
 */
abstract class BaseApiTest extends WebTestCase
{
    use DatabasePrimer;

    protected function setUp(): void
    {
        parent::setUp();
        // BaseApiTest ne réinitialise pas la BDD dans setUp()
        // Les tests doivent appeler resetDatabase() APRÈS createClient()
    }
}

