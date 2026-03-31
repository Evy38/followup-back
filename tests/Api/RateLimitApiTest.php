<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\DatabasePrimer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests du rate limiting et de la protection anti-énumération.
 *
 * Couvre les améliorations 5b et 5c :
 * - 5b : Rate limiting sur les endpoints publics (429 après N tentatives)
 * - 5c : Réponse identique quel que soit le type de compte (anti-énumération)
 *
 * Stratégie : chaque test utilise une IP unique via uniqid() pour éviter
 * que l'état du rate limiter d'un test ne pollue un autre test.
 */
class RateLimitApiTest extends WebTestCase
{
    use DatabasePrimer;

    // =========================================================================
    // 5b — RATE LIMITING
    // =========================================================================

    /**
     * POST /api/register : bloqué après 3 tentatives par IP.
     */
    public function test_register_is_rate_limited_after_3_attempts(): void
    {
        $client = static::createClient();
        $ip = '10.1.' . rand(0, 254) . '.' . rand(1, 254);
        $server = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip];

        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/register', [], [], $server, json_encode([
                'email' => "user{$i}_{$ip}@gmail.com",
                'password' => 'SecurePass123',
            ]));
            $this->assertNotEquals(
                Response::HTTP_TOO_MANY_REQUESTS,
                $client->getResponse()->getStatusCode(),
                "La requête #{$i} ne doit pas être bloquée par le rate limiter."
            );
        }

        // 4e tentative → 429
        $client->request('POST', '/api/register', [], [], $server, json_encode([
            'email' => "user4_{$ip}@gmail.com",
            'password' => 'SecurePass123',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    /**
     * POST /api/password/request : bloqué après 3 tentatives par IP.
     */
    public function test_forgot_password_is_rate_limited_after_3_attempts(): void
    {
        $client = static::createClient();
        $ip = '10.2.' . rand(0, 254) . '.' . rand(1, 254);
        $server = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip];

        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/password/request', [], [], $server, json_encode([
                'email' => 'nobody@example.com',
            ]));
            $this->assertNotEquals(
                Response::HTTP_TOO_MANY_REQUESTS,
                $client->getResponse()->getStatusCode(),
                "La requête #{$i} ne doit pas être bloquée par le rate limiter."
            );
        }

        // 4e tentative → 429
        $client->request('POST', '/api/password/request', [], [], $server, json_encode([
            'email' => 'nobody@example.com',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    /**
     * POST /api/verify-email/resend : bloqué après 3 tentatives par IP.
     */
    public function test_resend_verification_is_rate_limited_after_3_attempts(): void
    {
        $client = static::createClient();
        $ip = '10.3.' . rand(0, 254) . '.' . rand(1, 254);
        $server = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip];

        for ($i = 1; $i <= 3; $i++) {
            $client->request('POST', '/api/verify-email/resend', [], [], $server, json_encode([
                'email' => 'nobody@example.com',
            ]));
            $this->assertNotEquals(
                Response::HTTP_TOO_MANY_REQUESTS,
                $client->getResponse()->getStatusCode(),
                "La requête #{$i} ne doit pas être bloquée par le rate limiter."
            );
        }

        // 4e tentative → 429
        $client->request('POST', '/api/verify-email/resend', [], [], $server, json_encode([
            'email' => 'nobody@example.com',
        ]));
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    // =========================================================================
    // 5c — ANTI-ÉNUMÉRATION
    // =========================================================================

    /**
     * /api/password/request : email inexistant → message générique.
     */
    public function test_password_request_returns_generic_message_for_unknown_email(): void
    {
        $client = static::createClient();
        $ip = '10.4.' . rand(0, 254) . '.' . rand(1, 254);

        $client->request('POST', '/api/password/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => $ip,
        ], json_encode(['email' => 'doesnotexist@gmail.com']));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Si un compte existe',
            $body['message'],
            'Le message générique doit être retourné pour un email inexistant.'
        );
    }

    /**
     * /api/password/request : compte OAuth → même message générique qu'un email inexistant.
     *
     * Avant le fix, ce cas retournait un message spécifique révélant que le compte
     * existe ET qu'il utilise Google. C'est une fuite d'information (énumération).
     */
    public function test_password_request_returns_generic_message_for_oauth_account(): void
    {
        $client = static::createClient();

        // Créer un compte OAuth directement en BDD
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $oauthUser = new User();
        $oauthUser->setEmail('oauth-user@gmail.com');
        $oauthUser->setRoles(['ROLE_USER']);
        $oauthUser->setGoogleId('google-id-123');
        $oauthUser->setIsVerified(true);
        // Pas de mot de passe (compte OAuth)
        $oauthUser->setPassword($hasher->hashPassword($oauthUser, bin2hex(random_bytes(16))));

        $em->persist($oauthUser);
        $em->flush();

        $ip = '10.5.' . rand(0, 254) . '.' . rand(1, 254);

        $client->request('POST', '/api/password/request', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => $ip,
        ], json_encode(['email' => 'oauth-user@gmail.com']));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $body = json_decode($client->getResponse()->getContent(), true);

        // Le message doit être IDENTIQUE à celui d'un email inconnu
        $this->assertStringContainsString(
            'Si un compte existe',
            $body['message'],
            'Le message générique doit être retourné pour un compte OAuth (anti-énumération).'
        );

        // S'assurer qu'on ne révèle pas que c'est un compte Google
        $this->assertStringNotContainsString('Google', $body['message']);
        $this->assertStringNotContainsString('OAuth', $body['message']);
    }
}
