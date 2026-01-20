<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    private MailerInterface $mailer;
    private string $frontendUrl;

    public function __construct(
        MailerInterface $mailer,
        string $frontendUrl
    ) {
        $this->mailer = $mailer;
        $this->frontendUrl = rtrim($frontendUrl, '/');
    }

    /**
     * Génère le token et l’attache à l’utilisateur
     * ⚠️ NE FAIT AUCUN flush
     */
    public function generateVerificationToken(User $user): void
    {
        // Ne régénère le token que s'il n'existe pas ou est expiré
        if (!$user->getEmailVerificationToken() || !$user->isEmailVerificationTokenValid()) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = new \DateTimeImmutable('+24 hours');
            $user->setEmailVerificationToken($token);
            $user->setEmailVerificationTokenExpiresAt($expiresAt);
            $user->setIsVerified(false);
        }
    }

    /**
     * Envoie l’email de vérification
     */
    public function sendVerificationEmail(User $user): void
    {
        $token = $user->getEmailVerificationToken();

        if ($token === null) {
            throw new \LogicException('Le token de vérification est manquant.');
        }

        $verificationUrl = $this->frontendUrl . '/verify-email?token=' . $token;

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@followup.com', 'FollowUp'))
            ->to(new Address($user->getEmail()))
            ->subject('Confirmez votre adresse email - FollowUp')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $user->getEmailVerificationTokenExpiresAt(),
            ]);

        $this->mailer->send($email);
    }
}
