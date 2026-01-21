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
        $request = $this->requestStack->getCurrentRequest();
        $path = $request?->getPathInfo();


        // ❌ Ne PAS bloquer le login
        // Mais en environnement de test, loginUser ne passe pas par /api/login_check
        // On ne doit PAS bypasser la vérification pour les tests
        if ($path === '/api/login_check' && $_SERVER['APP_ENV'] !== 'test') {
            return;
        }

        $user = $event->getToken()->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Utilisateur invalide.');
        }

        if (!$user->isVerified()) {
            throw new AccessDeniedHttpException(
                'Compte non confirmé. Vérifiez votre email.'
            );
        }
    }
}
