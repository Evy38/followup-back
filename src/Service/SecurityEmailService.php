<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Envoie les emails de sécurité liés aux actions sensibles du compte utilisateur.
 *
 * Emails gérés :
 * - Changement de mot de passe (envoi immédiat via Mailer)
 * - Demande de suppression de compte (envoi différé via DeferredMailer)
 * - Confirmation de suppression de compte (envoi différé via DeferredMailer)
 *
 * Les emails de suppression sont envoyés en différé via {@see DeferredMailer}
 * pour ne pas bloquer la réponse HTTP, car le compte est supprimé (ou marqué)
 * avant l'envoi.
 */
class SecurityEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly DeferredMailer $deferredMailer,
        private readonly string $frontendUrl
    ) {}

    /**
     * Notifie l'utilisateur que son mot de passe vient d'être modifié.
     * Envoi immédiat — utilisé comme alerte de sécurité.
     */
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

    /**
     * Notifie l'utilisateur que son prénom ou nom vient d'être modifié.
     * Envoi immédiat — utilisé comme alerte de sécurité.
     */
    public function sendProfileNameChangedEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@followup.fr', 'FollowUp Sécurité'))
            ->to($user->getEmail())
            ->subject('[FollowUp] Vos informations de profil ont été modifiées')
            ->htmlTemplate('emails/profile_name_changed.html.twig')
            ->context([
                'user' => $user,
                'changedAt' => new \DateTimeImmutable(),
                'supportUrl' => $this->frontendUrl . '/support',
            ]);

        $this->mailer->send($email);
    }

    /**
     * Confirme à l'utilisateur que sa demande de suppression a bien été reçue.
     * Envoi différé via DeferredMailer (la requête ne doit pas être bloquée).
     */
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

        $this->deferredMailer->queue($email);
    }

    /**
     * Informe l'utilisateur que son compte a été définitivement supprimé.
     * Envoi différé via DeferredMailer. Prend l'email en string car le compte
     * est supprimé au moment de l'appel et l'entité User peut être effacée.
     *
     * @param string $email     Adresse email de l'utilisateur supprimé
     * @param string $firstName Prénom pour personnaliser l'email
     */
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

        $this->deferredMailer->queue($emailMessage);
    }
}