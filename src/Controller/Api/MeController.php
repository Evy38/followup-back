<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MeController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'authenticated' => false,
                'verified' => false,
                'user' => null,
            ], 401);
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
                    'googleId' => $user->getGoogleId(),
                ],
            ], 403);
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
                'googleId' => $user->getGoogleId(),
            ],
        ]);
    }

}
