<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BasicTest extends KernelTestCase
{
    use DatabasePrimer;

    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testDatabaseConnection(): void
    {
        $conn = $this->entityManager->getConnection();
        $result = $conn->fetchOne('SELECT 1');

        $this->assertEquals(1, $result);
    }

}
