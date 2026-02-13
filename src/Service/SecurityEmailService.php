<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class SecurityEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl
    ) {}

    public function sendPasswordChangedEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@followup.fr', 'FollowUp Sécurité'))
            ->to($user->getEmail())
            ->subject('[FollowUp] Votre mot de passe a été modifié')
            ->htmlTemplate('emails/password_changed.html.twig')
            ->context([
                'user' => $user,
                'changedAt' => new \DateTimeImmutable(),
                'supportUrl' => $this->frontendUrl . '/support',
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountDeletionRequestEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@followup.fr', 'FollowUp'))
            ->to($user->getEmail())
            ->subject('[FollowUp] Demande de suppression de compte')
            ->htmlTemplate('emails/account_deletion_request.html.twig')
            ->context([
                'user' => $user,
                'requestedAt' => $user->getDeletionRequestedAt(),
                'supportUrl' => $this->frontendUrl . '/support',
            ]);

        $this->mailer->send($email);
    }

    public function sendAccountDeletionConfirmationEmail(string $email, string $firstName): void
    {
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('no-reply@followup.fr', 'FollowUp'))
            ->to($email)
            ->subject('[FollowUp] Votre compte a été supprimé')
            ->htmlTemplate('emails/account_deleted.html.twig')
            ->context([
                'firstName' => $firstName,
                'deletedAt' => new \DateTimeImmutable(),
            ]);

        $this->mailer->send($emailMessage);
    }
}