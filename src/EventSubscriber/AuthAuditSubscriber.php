<?php

namespace App\EventSubscriber;

use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Logue les connexions réussies et échouées dans le canal audit.
 */
class AuthAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->auditLogger->log('login_success', $event->getUser()->getUserIdentifier());
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $passport = $event->getPassport();
        $email = $passport?->getBadge(\Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge::class)?->getUserIdentifier() ?? 'inconnu';

        $this->auditLogger->log('login_failure', $email, [
            'reason' => $event->getException()?->getMessage(),
        ]);
    }
}
