<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class SecurityEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    public function sendPasswordChangedEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@followup.fr', 'FollowUp Sécurité'))
            ->to($user->getEmail())
            ->subject('Votre mot de passe a été modifié')
            ->htmlTemplate('emails/password_changed.html.twig')
            ->context([
                'user' => $user,
                'changedAt' => new \DateTimeImmutable(),
            ]);

        $this->mailer->send($email);
    }
}
