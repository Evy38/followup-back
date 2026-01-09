<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private string $frontendUrl;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        string $frontendUrl
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->frontendUrl = rtrim($frontendUrl, '/');
    }

    /**
     * Génère un token de vérification et envoie l'email
     */
    public function sendVerificationEmail(User $user): void
    {
        // Génération du token
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+24 hours');

        // Stockage sur l'utilisateur
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt($expiresAt);
        $user->setIsVerified(false);

        $this->entityManager->flush();

        // Construction de l'URL de confirmation
        $verificationUrl = $this->frontendUrl . '/verify-email?token=' . $token;

        // Email
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@followup.com', 'FollowUp'))
            ->to(new Address($user->getEmail()))
            ->subject('Confirmez votre adresse email - FollowUp')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $expiresAt,
            ]);

        $this->mailer->send($email);
    }
}
