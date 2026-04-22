<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Retourne les candidatures de l'utilisateur authentifié, triées par date décroissante.
 *
 * Endpoints :
 * - GET /api/my-candidatures               Candidatures actives (non archivées)
 * - GET /api/my-candidatures?archived=true Candidatures archivées uniquement
 *
 * Complément à l'endpoint API Platform GET /api/candidatures, optimisé pour le tableau
 * de bord : retourne uniquement les candidatures du user courant sans avoir à filtrer côté client.
 */
#[Route('/api/my-candidatures')]
class MyCandidaturesController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, CandidatureRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isDeleted()) {
            return $this->json([
                'error' => 'Ce compte est supprimé.'
            ], 403);
        }

        $showArchived = $request->query->getBoolean('archived', false);

        $candidatures = $showArchived
            ? $repo->findArchivedByUser($user)
            : $repo->findActiveByUser($user);

        return $this->json(
            $candidatures,
            200,
            ['Cache-Control' => 'no-store, no-cache, must-revalidate'],
            ['groups' => ['candidature:read']]
        );
    }
}