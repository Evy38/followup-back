<?php

namespace App\Controller\Api;

use App\Entity\Candidature;
use App\Enum\StatutReponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la mise à jour manuelle du statut de réponse d'une candidature.
 * 
 * ⚠️ La mise à jour manuelle n'est autorisée QUE si aucun entretien n'existe.
 * Si des entretiens existent, le statut est géré automatiquement par CandidatureStatutSyncService.
 */
#[Route('/api/candidatures')]
#[IsGranted('ROLE_USER')]
class CandidatureReponseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * Met à jour manuellement le statut de réponse d'une candidature.
     * 
     * Restrictions :
     * - Uniquement pour les candidatures sans entretien
     * - L'utilisateur doit être le propriétaire de la candidature
     * 
     * @param int $id ID de la candidature
     * @param Request $request Contient le nouveau statutReponse
     * @return JsonResponse La candidature mise à jour
     * 
     * @throws NotFoundHttpException Si la candidature n'existe pas
     * @throws AccessDeniedHttpException Si l'utilisateur n'est pas propriétaire
     * @throws BadRequestHttpException Si le statut est invalide ou si des entretiens existent
     */
    #[Route('/{id}/statut-reponse', name: 'api_candidature_update_statut_reponse', methods: ['PATCH'])]
    public function updateStatutReponse(int $id, Request $request): JsonResponse
    {
        // 1️⃣ Récupération de la candidature
        $candidature = $this->em->getRepository(Candidature::class)->find($id);

        if (!$candidature) {
            throw new NotFoundHttpException('Candidature introuvable.');
        }

        // 2️⃣ Vérification des droits d'accès
        if ($candidature->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Vous ne pouvez modifier que vos propres candidatures.');
        }

        // 3️⃣ Lecture et validation du payload
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['statutReponse'])) {
            throw new BadRequestHttpException('Le champ "statutReponse" est requis.');
        }

        // 4️⃣ Vérification : pas d'entretien existant
        if (!$candidature->getEntretiens()->isEmpty()) {
            throw new BadRequestHttpException(
                'Le statut de réponse est géré automatiquement car des entretiens existent pour cette candidature.'
            );
        }

        // 5️⃣ Conversion string → Enum
        try {
            $nouveauStatut = StatutReponse::from($data['statutReponse']);
        } catch (\ValueError $e) {
            $valeursValides = implode(', ', array_map(fn($case) => $case->value, StatutReponse::cases()));
            throw new BadRequestHttpException(
                sprintf('Statut invalide. Valeurs autorisées : %s', $valeursValides)
            );
        }

        // 6️⃣ Mise à jour
        $candidature->setStatutReponse($nouveauStatut);
        $this->em->flush();

        // 7️⃣ Réponse
        return $this->json([
            'id' => $candidature->getId(),
            'statutReponse' => $candidature->getStatutReponse()->value,
        ]);
    }
}