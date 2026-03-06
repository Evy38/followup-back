<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Retourne les candidatures de l'utilisateur authentifié, triées par date décroissante.
 *
 * Endpoint :
 * - GET /api/my-candidatures   Liste complète des candidatures de l'utilisateur connecté
 *
 * Complément à l'endpoint API Platform GET /api/candidatures, optimisé pour le tableau
 * de bord : retourne uniquement les candidatures du user courant sans avoir à filtrer côté client.
 */
#[Route('/api/my-candidatures')]
class MyCandidaturesController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(CandidatureRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est supprimé.'
            ], 403);
        }

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