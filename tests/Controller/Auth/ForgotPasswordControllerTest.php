<?php

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordControllerTest extends WebTestCase
{
    /**
     * 🔴 EMAIL MANQUANT → 400
     */
    public function testPasswordRequestFailsWhenEmailMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals("L'adresse email est requise.", $responseData['error'] ?? null);
    }

    /**
     * 🔴 EMAIL INVALIDE → 400
     */
    public function testPasswordRequestFailsWithInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'not-an-email'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            "Adresse email invalide.",
            $client->getResponse()->getContent()
        );
    }

    /**
     * ✅ EMAIL INCONNU → 200 (anti-enumération)
     */
    public function testPasswordRequestSucceedsWhenUserNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'unknown_' . uniqid() . '@example.com'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.', $responseData['message'] ?? null);
    }

    /**
     * ✅ EMAIL EXISTANT → token généré
     */
    public function testPasswordRequestGeneratesResetToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('reset_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $user->getEmail()
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // 🔍 Vérification base
        $em->refresh($user);
        $this->assertNotNull($user->getResetPasswordToken());
        $this->assertNotNull($user->getResetPasswordTokenExpiresAt());
    }

    /**
     * 🔴 RESET — données manquantes → 400
     */
    public function testResetPasswordFailsWhenDataMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * 🔴 RESET — mots de passe différents → 400
     */
    public function testResetPasswordFailsWhenPasswordsDoNotMatch(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'any-token',
                'newPassword' => 'Password1',
                'confirmPassword' => 'Password2'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertStringContainsString(
            'Les mots de passe ne correspondent pas.',
            $client->getResponse()->getContent()
        );
    }

    /**
     * 🔴 RESET — mot de passe trop faible → 400
     */
    public function testResetPasswordFailsWithWeakPassword(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'any-token',
                'newPassword' => 'weakpass'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * 🔴 RESET — token invalide → 400
     */
    public function testResetPasswordFailsWithInvalidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'invalid-token',
                'newPassword' => 'StrongPass1'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token invalide ou expiré.', $responseData['error'] ?? null);
    }

    /**
     * 🔴 RESET — token expiré → 400
     */
    public function testResetPasswordFailsWithExpiredToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('expired_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setResetPasswordToken('expired-token');
        $user->setResetPasswordTokenExpiresAt(
            new \DateTimeImmutable('-1 hour')
        );

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'expired-token',
                'newPassword' => 'StrongPass1'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token expiré. Veuillez faire une nouvelle demande de réinitialisation.', $responseData['error'] ?? null);
    }

    /**
     * ✅ RESET — succès → 200
     */
    public function testResetPasswordSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $token = 'valid-reset-token-' . uniqid();

        $user = new User();
        $user->setEmail('success_' . uniqid() . '@example.com');
        $user->setPassword('oldpassword');
        $user->setRoles(['ROLE_USER']);
        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt(
            new \DateTimeImmutable('+1 hour')
        );

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => $token,
                'newPassword' => 'NewStrongPass1'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Mot de passe mis à jour avec succès.', $responseData['message'] ?? null);

        // 🔍 Vérifie que le token a été consommé
        $em->refresh($user);
        $this->assertNull($user->getResetPasswordToken());
        $this->assertNull($user->getResetPasswordTokenExpiresAt());
    }
}
