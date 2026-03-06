<?php

namespace App\Service;

use Google\Client;

/**
 * Fournit un client Google OAuth 2.0 configuré pour FollowUp.
 *
 * Ce service encapsule la configuration de la bibliothèque `google/apiclient`
 * avec les credentials définis dans les variables d'environnement
 * (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`).
 *
 * Scopes demandés : `email` et `profile` (lecture seule, pas d'accès aux données Google).
 *
 * @see \App\Controller\Auth\AuthController
 */
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

    /**
     * Instancie et configure le client Google OAuth avec les credentials de l'application.
     *
     * @return Client Client Google prêt à générer l'URL d'autorisation ou échanger un code
     */
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
