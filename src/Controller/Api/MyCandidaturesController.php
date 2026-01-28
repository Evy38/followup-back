<?php

namespace App\Controller\Api;

use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/my-candidatures')]
class MyCandidaturesController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(CandidatureRepository $repo): JsonResponse
    {
        $user = $this->getUser();

        $candidatures = $repo->findBy(
            ['user' => $user],
            ['dateCandidature' => 'DESC']
        );

        return $this->json(
            $candidatures,
            200,
            [],
            ['groups' => ['candidature:read']]
        );
    }
}

