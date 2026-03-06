<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Tableau de bord et statistiques réservés à l'administrateur.
 *
 * Endpoint :
 * - GET /api/admin/dashboard   Retourne les statistiques globales des utilisateurs
 *   (actifs, supprimés, en attente de suppression)
 *
 * Toutes les routes de ce contrôleur nécessitent ROLE_ADMIN.
 */
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