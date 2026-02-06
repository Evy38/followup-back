<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour récupérer les informations de l'utilisateur connecté.
 * 
 * Utilisé par le frontend pour :
 * - Vérifier l'authentification JWT
 * - Récupérer les données utilisateur (profil, rôles)
 * - Vérifier si l'email est vérifié
 */
class MeController extends AbstractController
{
    /**
     * Récupère les informations de l'utilisateur connecté.
     * 
     * Réponses possibles :
     * - 200 : Utilisateur authentifié et vérifié
     * - 403 : Utilisateur authentifié mais email non vérifié
     * - 401 : Non authentifié
     * 
     * @return JsonResponse Les données utilisateur avec statut d'authentification
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        // Cas 1 : Utilisateur non authentifié
        if (!$user instanceof User) {
            return $this->json([
                'authenticated' => false,
                'verified' => false,
                'user' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Cas 2 : Utilisateur authentifié mais email non vérifié
        if (!$user->isVerified()) {
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

        // Cas 3 : Utilisateur authentifié et vérifié
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
            ],
        ], Response::HTTP_OK);
    }
}