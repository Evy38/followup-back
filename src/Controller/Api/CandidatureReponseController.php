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
 * L'utilisateur peut modifier le statut librement, même avec des entretiens existants.
 */
#[Route('/api/candidatures')]
#[IsGranted('ROLE_USER')]
class CandidatureReponseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }


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