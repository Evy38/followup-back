<?php

namespace App\Controller\Api;

use App\Entity\Candidature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/candidatures')]
class CandidatureReponseController extends AbstractController
{

    #[Route('/{id}/statut-reponse', name: 'api_candidature_update_statut_reponse', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatutReponse(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // 1️⃣ On récupère la candidature
        $candidature = $em->getRepository(Candidature::class)->find($id);

        if (!$candidature) {
            return $this->json(['error' => 'Candidature introuvable'], 404);
        }

        // 2️⃣ Sécurité : l’utilisateur ne peut modifier QUE ses candidatures
        if ($candidature->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // 3️⃣ Lecture du payload JSON
        $data = json_decode($request->getContent(), true);


        if (!is_array($data)) {
            return new JsonResponse(['error' => 'JSON invalide.'], 400);
        }

        if (!isset($data['statutReponse'])) {
            return $this->json(['error' => 'statutReponse manquant'], 400);
        }

        try {

            if (!$candidature->getEntretiens()->isEmpty()) {
                return $this->json([
                    'error' => 'Le statut est géré automatiquement car des entretiens existent.'
                ], 400);
            }

            // 4️⃣ Mise à jour contrôlée
            // La validation est faite directement dans l’entité
            $candidature->setStatutReponse($data['statutReponse']);

            // 5️⃣ Sauvegarde
            $em->flush();

            // 6️⃣ Réponse API claire
            return $this->json([
                'id' => $candidature->getId(),
                'statutReponse' => $candidature->getStatutReponse(),
            ]);

        } catch (\InvalidArgumentException $e) {
            // Erreur métier propre (statut invalide)
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

}
