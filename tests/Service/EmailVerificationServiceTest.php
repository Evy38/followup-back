<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\EmailVerificationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailVerificationServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->service = new EmailVerificationService(
            $this->mailer,
            'http://localhost:4200'
        );
    }

    /**
     * Génère un token si aucun n'existe
     */
    public function testGenerateVerificationTokenCreatesToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->service->generateVerificationToken($user);

        $this->assertNotNull($user->getEmailVerificationToken());
        $this->assertNotNull($user->getEmailVerificationTokenExpiresAt());
        $this->assertFalse($user->isVerified());
    }

    /**
     * Ne régénère PAS le token s'il est encore valide
     */
    public function testGenerateVerificationTokenDoesNotOverrideValidToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $user->setEmailVerificationToken('existing-token');
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable('+2 hours')
        );

        $this->service->generateVerificationToken($user);

        $this->assertSame('existing-token', $user->getEmailVerificationToken());
    }

    /**
     * 🔴 Régénère le token s'il est expiré
     */
    public function testGenerateVerificationTokenRegeneratesExpiredToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $user->setEmailVerificationToken('expired-token');
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable('-1 hour')
        );

        $this->service->generateVerificationToken($user);

        $this->assertNotEquals('expired-token', $user->getEmailVerificationToken());
        $this->assertNotNull($user->getEmailVerificationTokenExpiresAt());
    }

    /**
     * 🔴 Envoi impossible si token absent
     */
    public function testSendVerificationEmailFailsWithoutToken(): void
    {
        $this->expectException(\LogicException::class);

        $user = new User();
        $user->setEmail('test@example.com');

        $this->service->sendVerificationEmail($user);
    }

    /**
     * Envoi de l’email avec le bon contenu
     */
    public function testSendVerificationEmailSendsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setEmailVerificationToken('valid-token');
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable('+24 hours')
        );

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($user) {
                return
                    $email->getTo()[0]->getAddress() === $user->getEmail()
                    && str_contains($email->getSubject(), 'Confirmez votre adresse email');
            }));

        $this->service->sendVerificationEmail($user);
    }
}
