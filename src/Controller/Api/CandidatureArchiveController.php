<?php

namespace App\Controller\Api;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gère l'archivage et le désarchivage des candidatures.
 *
 * Endpoints :
 * - POST /api/candidatures/{id}/archive    Archive une candidature (la retire du pipeline actif)
 * - POST /api/candidatures/{id}/unarchive  Restaure une candidature archivée dans le pipeline
 */
#[Route('/api/candidatures/{id}')]
#[IsGranted('ROLE_USER')]
class CandidatureArchiveController extends AbstractController
{
    #[Route('/archive', methods: ['POST'])]
    public function archive(
        string $id,
        CandidatureRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $candidature = $repo->find($id);

        if (!$candidature) {
            return $this->json(['error' => 'Candidature introuvable.'], 404);
        }

        if ($candidature->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $candidature->setArchivedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json(
            $candidature,
            200,
            [],
            ['groups' => ['candidature:read']]
        );
    }

    #[Route('/unarchive', methods: ['POST'])]
    public function unarchive(
        string $id,
        CandidatureRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $candidature = $repo->find($id);

        if (!$candidature) {
            return $this->json(['error' => 'Candidature introuvable.'], 404);
        }

        if ($candidature->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $candidature->setArchivedAt(null);
        $em->flush();

        return $this->json(
            $candidature,
            200,
            [],
            ['groups' => ['candidature:read']]
        );
    }
}
