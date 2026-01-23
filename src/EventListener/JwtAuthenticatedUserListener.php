<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class JwtAuthenticatedUserListener
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function onJwtAuthenticated(JWTAuthenticatedEvent $event): void
    {
        error_log('====================');
        error_log('[JWT LISTENER] JWT AUTHENTICATED');

        $user = $event->getToken()->getUser();

        error_log('[JWT LISTENER] User class = ' . (is_object($user) ? get_class($user) : 'NOT OBJECT'));

        if ($user instanceof User) {
            error_log('[JWT LISTENER] User ID = ' . $user->getId());
            error_log('[JWT LISTENER] Email = ' . $user->getEmail());
            error_log('[JWT LISTENER] isVerified = ' . ($user->isVerified() ? 'true' : 'false'));
        }

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Utilisateur invalide.');
        }
    }
}
