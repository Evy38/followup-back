<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JobControllerTest extends WebTestCase
{
    public function testSearchReturnsJsonArray()
    {
        $client = static::createClient();
        $client->request('GET', '/api/jobs');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        if (count($data) > 0) {
            $job = $data[0];
            $this->assertArrayHasKey('externalId', $job);
            $this->assertArrayHasKey('title', $job);
            $this->assertArrayHasKey('company', $job);
            $this->assertArrayHasKey('location', $job);
            $this->assertArrayHasKey('contractType', $job);
            $this->assertArrayHasKey('salaryMin', $job);
            $this->assertArrayHasKey('salaryMax', $job);
            $this->assertArrayHasKey('redirectUrl', $job);
        }
    }
}
