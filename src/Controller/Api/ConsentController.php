<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Enregistre le consentement RGPD de l'utilisateur authentifié.
 *
 * Endpoint :
 * - POST /api/me/consent   Marque `consentRgpd = true` avec la date de consentement
 *
 * L'opération est idempotente : si le consentement est déjà enregistré, aucune modification
 * n'est effectuée en base. Utilisé après connexion OAuth où le consentement n'est pas recueilli
 * à l'inscription.
 */
class ConsentController extends AbstractController
{
    #[Route('/api/me/consent', name: 'api_me_consent', methods: ['POST'])]
    public function consent(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getConsentRgpd()) {
            $user->setConsentRgpd(true);
            $user->setConsentRgpdAt(new \DateTimeImmutable());
            $em->persist($user);
            $em->flush();
        }

        return $this->json(['message' => 'Consentement enregistré'], Response::HTTP_OK);
    }
}
