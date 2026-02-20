<?php

namespace App\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Envoie les emails APRÈS que la réponse HTTP a été transmise au client.
 * Utilise l'événement kernel.terminate qui se déclenche après $response->send().
 */
class DeferredMailer implements EventSubscriberInterface
{
    /** @var Email[] */
    private array $queue = [];

    public function __construct(private readonly MailerInterface $mailer) {}

    public function queue(Email $email): void
    {
        $this->queue[] = $email;
    }

    public function onKernelTerminate(): void
    {
        foreach ($this->queue as $email) {
            try {
                $this->mailer->send($email);
            } catch (\Throwable $e) {
                // Ne bloque pas — l'email est non-critique
            }
        }
        $this->queue = [];
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onKernelTerminate'];
    }
}
