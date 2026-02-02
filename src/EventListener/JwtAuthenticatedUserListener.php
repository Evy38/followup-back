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
        
        $user = $event->getToken()->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Utilisateur invalide.');
        }
    }
}
