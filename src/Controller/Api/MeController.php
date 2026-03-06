<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Retourne le profil de l'utilisateur authentifié par JWT.
 *
 * Endpoint :
 * - GET /api/me   Retourne l'état d'authentification et les informations du compte
 *
 * Réponses possibles :
 * - 200 : compte actif et vérifié — renvoie id, email, prénom, nom, rôles, OAuth, consentRGPD
 * - 401 : aucun JWT valide
 * - 403 : compte supprimé ou email non vérifié (avec `authenticated: true, verified: false`)
 *
 * Ce endpoint est utilisé par le frontend pour vérifier l'état du compte au démarrage.
 */
class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'authenticated' => false,
                'verified' => false,
                'user' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isDeleted()) {
            return $this->json([
                'authenticated' => false,
                'verified' => false,
                'user' => null,
                'error' => 'Ce compte a été supprimé.'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$user->getIsVerified()) {
            return $this->json([
                'authenticated' => true,
                'verified' => false,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles(),
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'authenticated' => true,
            'verified' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'isOAuth' => $user->isOauthUser(),
                'consentRgpd' => $user->getConsentRgpd(),
                'consentRgpdAt' => $user->getConsentRgpdAt()?->format(DATE_ATOM),
            ],
        ], Response::HTTP_OK);
    }
}