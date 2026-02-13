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
     * Génère un nouveau token de vérification
     * ⚠️ NE FAIT AUCUN flush
     * ⚠️ Ne touche PAS à isVerified
     */
    public function generateVerificationToken(User $user): void
    {
        // Génération systématique d'un nouveau token
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+24 hours');
        
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);
    }

    /**
     * Envoie l'email de vérification
     * - À l'inscription : envoie à l'email principal
     * - Au changement d'email : envoie au pendingEmail
     */
    public function sendVerificationEmail(User $user): void
    {
        $token = $user->getEmailVerificationToken();

        if ($token === null) {
            throw new \LogicException('Le token de vérification est manquant.');
        }

        $verificationUrl = $this->frontendUrl . '/verify-email?token=' . $token;

        // Si pendingEmail existe, on envoie là-bas (changement d'email)
        // Sinon on envoie à l'email principal (inscription)
        $targetEmail = $user->getPendingEmail() ?? $user->getEmail();

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@followup.com', 'FollowUp'))
            ->to(new Address($targetEmail))
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