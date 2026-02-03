<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        return $this->json([
            'message' => 'Accès administrateur autorisé',
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);
    }
}
