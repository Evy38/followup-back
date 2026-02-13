<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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