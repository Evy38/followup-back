<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Entity\User;

class JwtAuthenticatedUserListener
{
    public function onJwtAuthenticated(JWTAuthenticatedEvent $event): void
    {
        $user = $event->getToken()->getUser();

        // Sécurité supplémentaire
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Utilisateur invalide.');
        }

        // ⛔ Compte non vérifié → accès interdit
        if (!$user->isVerified()) {
            throw new AccessDeniedHttpException(
                'Votre compte n’est pas encore confirmé. Veuillez vérifier votre email.'
            );
        }
    }
}
