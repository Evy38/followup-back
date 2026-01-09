<?php

namespace App\Service;

use Google\Client;

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getClient(): Client
    {
        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);

        // On demande juste l’email + profile
        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }
}
