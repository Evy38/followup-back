<?php

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailControllerTest extends WebTestCase
{
    /**
     * 🔴 Token manquant → 400
     */
    public function testVerifyEmailFailsWhenTokenMissing(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/verify-email');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'Token manquant',
            $client->getResponse()->getContent()
        );
    }

    /**
     * 🔴 Token invalide → 400
     */
    public function testVerifyEmailFailsWithInvalidToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/verify-email?token=invalid-token');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'Token invalide',
            $client->getResponse()->getContent()
        );
    }

    /**
     * 🔴 Token expiré → 400
     */
    public function testVerifyEmailFailsWithExpiredToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('expired_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setEmailVerificationToken('expired-token');
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable('-1 hour')
        );

        $em->persist($user);
        $em->flush();

        $client->request(
            'GET',
            '/api/verify-email?token=expired-token'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token expiré', $responseData['error'] ?? null);
    }

    /**
     * ✅ Validation réussie → 200
     */
    public function testVerifyEmailSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $token = 'valid-token-' . uniqid();

        $user = new User();
        $user->setEmail('valid_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable('+1 hour')
        );

        $em->persist($user);
        $em->flush();

        $client->request(
            'GET',
            '/api/verify-email?token=' . $token
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email confirmé', $responseData['message'] ?? null);

        // 🔍 Vérification état base
        $em->refresh($user);
        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getEmailVerificationToken());
        $this->assertNull($user->getEmailVerificationTokenExpiresAt());
    }

    /**
     * 🔁 Idempotence : email déjà confirmé → 200
     */
    public function testVerifyEmailAlreadyConfirmed(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('verified_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);

        $em->persist($user);
        $em->flush();

        $client->request(
            'GET',
            '/api/verify-email?token=random-token'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email déjà confirmé.', $responseData['message'] ?? null);
    }

    /**
     * 🔴 RESEND — email manquant → 400
     */
    public function testResendVerificationEmailFailsWhenEmailMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/verify-email/resend',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'Email manquant.',
            $client->getResponse()->getContent()
        );
    }

    /**
     * 🔴 RESEND — user inexistant → 404
     */
    public function testResendVerificationEmailFailsWhenUserNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/verify-email/resend',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'unknown@example.com'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * 🔴 RESEND — compte déjà confirmé → 400
     */
    public function testResendVerificationEmailFailsWhenAlreadyVerified(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('verified_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/verify-email/resend',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $user->getEmail()
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Ce compte est déjà confirmé.', $responseData['message'] ?? null);
    }

    /**
     * ✅ RESEND — succès → 200
     */
    public function testResendVerificationEmailSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('resend_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/verify-email/resend',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $user->getEmail()
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email de confirmation renvoyé.', $responseData['message'] ?? null);

        // 🔍 Vérifie que le token a été généré
        $em->refresh($user);
        $this->assertNotNull($user->getEmailVerificationToken());
    }
}
