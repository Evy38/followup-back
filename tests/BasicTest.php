<?php

namespace App\Tests;

class BasicTest extends DatabaseTestCase
{
    public function testDatabaseConnection(): void
    {
        $conn = $this->entityManager->getConnection();
        $this->assertTrue($conn->isConnected());
    }
}
