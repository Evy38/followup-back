<?php

namespace App\Controller\Api;

use App\Service\AdzunaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/jobs')]
class JobController extends AbstractController
{
    public function __construct(
        private readonly AdzunaService $adzunaService
    ) {}

    #[Route('', methods: ['GET'])]
    public function search(): JsonResponse
    {
        return $this->json(
            $this->adzunaService->search('developer', 'paris')
        );
    }
}
