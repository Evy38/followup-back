<?php

namespace App\Tests\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\MailerInterface;

class ForgotPasswordControllerTest extends WebTestCase
{
    public function testRequestPasswordResetFailsWhenEmailMissing(): void
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

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRequestPasswordResetFailsWithInvalidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRequestPasswordResetWithUnknownEmailReturnsGenericMessage(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'unknown@test.com'])
        );

        $this->assertResponseIsSuccessful();
        
        // ✅ Assertion correcte
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.',
            $responseData['message'] ?? null
        );
    }

    public function testRequestPasswordResetWithExistingEmailGeneratesToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        // Mock du mailer
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');
        $container->set(MailerInterface::class, $mailer);

        // Création d'un utilisateur
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setPassword('hashed-password');
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/password/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'user@test.com'])
        );

        $this->assertResponseIsSuccessful();

        $em->refresh($user);
        $this->assertNotNull($user->getResetPasswordToken());
        $this->assertNotNull($user->getResetPasswordTokenExpiresAt());
    }

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
                'newPassword' => 'Password1'
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }

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
                'token' => 'token',
                'newPassword' => 'weak'
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }

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
                'token' => 'token',
                'newPassword' => 'Password1',
                'confirmPassword' => 'Password2'
            ])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordSuccessWithValidToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('reset@test.com');
        $user->setPassword('old-password');
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        $user->setResetPasswordToken('valid-token');
        $user->setResetPasswordTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/password/reset',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => 'valid-token',
                'newPassword' => 'NewPassword1',
                'confirmPassword' => 'NewPassword1'
            ])
        );

        $this->assertResponseIsSuccessful();

        $em->refresh($user);
        $this->assertNull($user->getResetPasswordToken());
        $this->assertNull($user->getResetPasswordTokenExpiresAt());
    }
}