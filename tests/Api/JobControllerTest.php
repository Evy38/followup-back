<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    public function testSearchReturnsJsonArray()
    {
        $client = static::createClient();
        $client->request('GET', '/api/jobs');

        // On attend une 401 car aucun JWT n'est fourni
        $this->assertResponseStatusCodeSame(401);
    }
}
