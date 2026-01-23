<?php

namespace App\Tests\Service;

use App\Service\GoogleAuthService;
use Google\Client;
use PHPUnit\Framework\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    public function testGetClientReturnsConfiguredGoogleClient(): void
    {
        $service = new GoogleAuthService(
            'test-client-id',
            'test-client-secret',
            'http://localhost/callback'
        );

        $client = $service->getClient();

        $this->assertInstanceOf(Client::class, $client);

        $this->assertSame('test-client-id', $client->getClientId());
        $this->assertSame('test-client-secret', $client->getClientSecret());
        $this->assertSame('http://localhost/callback', $client->getRedirectUri());

        $scopes = $client->getScopes();

        $this->assertContains('email', $scopes);
        $this->assertContains('profile', $scopes);
    }
}
