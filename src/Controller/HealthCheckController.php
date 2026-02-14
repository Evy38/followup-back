<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 🏥 Healthcheck controller pour Render (service d'hébergement)
 * Endpoint public qui permet à Render de vérifier que l'app est en ligne
 */
class HealthCheckController extends AbstractController
{
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): Response
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('c'),
        ]);
    }

    #[Route('/api', name: 'api_index', methods: ['GET'])]
    public function apiIndex(): Response
    {
        return $this->json([
            'message' => 'FollowUp API - Documentation: /api/docs',
            'version' => '1.0.0',
        ]);
    }
}
