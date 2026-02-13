<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Event Listener pour la validation post-authentification JWT.
 * 
 * Vérifie que l'utilisateur authentifié par JWT est bien une instance valide de User.
 * 
 * Ce listener est déclenché après qu'un token JWT a été validé avec succès,
 * mais avant que la requête ne soit traitée par le controller.
 * 
 * Cas d'usage :
 * - Validation supplémentaire après décodage du JWT
 * - Vérification de l'état du compte (banni, supprimé, etc.)
 * - Enrichissement du contexte utilisateur
 */
class JwtAuthenticatedUserListener
{
    /**
     * Callback déclenché après authentification JWT réussie.
     * 
     * @param JWTAuthenticatedEvent $event Contient le token JWT et l'utilisateur
     * 
     * @throws AccessDeniedHttpException Si l'utilisateur n'est pas une instance valide
     */
    public function onJwtAuthenticated(JWTAuthenticatedEvent $event): void
    {
        $user = $event->getToken()->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Utilisateur invalide.');
        }

        if ($user->isDeleted()) {
            throw new AccessDeniedHttpException('Compte supprimé.');
        }

        if (!$user->getIsVerified()) {
            throw new AccessDeniedHttpException('Email non vérifié.');
        }
    }



}