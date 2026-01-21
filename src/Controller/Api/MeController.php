<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // Ici, si le JWT est invalide -> Symfony renverra 401 avant d'arriver ici
        // Si le user n'est pas verified -> ton listener renverra 403 avant d'arriver ici
        $user = $this->getUser();

        return new JsonResponse([
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
        ]);
    }
}
