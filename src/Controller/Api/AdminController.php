<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        return $this->json([
            'message' => 'Accès administrateur autorisé',
            'admin' => $this->getUser()?->getUserIdentifier(),
            'stats' => [
                'activeUsers' => $this->userRepository->countActiveUsers(),
                'deletedUsers' => $this->userRepository->countDeletedUsers(),
                'pendingDeletion' => count($this->userRepository->findPendingDeletion()),
            ],
        ]);
    }
}