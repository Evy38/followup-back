<?php

namespace App\Controller\Api;

use App\Entity\Candidature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller dédié à la gestion du statut global d’une candidature.
 *
 * ⚠️ IMPORTANT :
 * - La gestion des entretiens est désormais assurée par l’entité Entretien
 *   et ses processors API Platform.
 * - Ce controller ne fait que des mises à jour simples et ciblées.
 *
 * Logique métier :
 * - Le frontend ne calcule jamais le statut.
 * - Il demande une intention → le backend décide.
 */
#[Route('/api/candidatures')]
class CandidatureReponseController extends AbstractController
{
    /**
     * Met à jour le statut global de réponse de la candidature.
     *
     * Cas d’usage :
     * - Pas encore de réponse → "attente"
     * - Échanges en cours → "echanges"
     * - Refus explicite → "negative"
     *
     * ⚠️ Ce endpoint NE gère PAS les entretiens.
     * Dès qu’un entretien existe, la synchronisation est gérée ailleurs.
     */
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

        if (!isset($data['statutReponse'])) {
            return $this->json(['error' => 'statutReponse manquant'], 400);
        }

        try {
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

    /**
     * 🧓 ENDPOINT LEGACY — À SUPPRIMER PLUS TARD
     *
     * Ancienne logique :
     * - La candidature stockait directement une date/heure d’entretien.
     *
     * Problème :
     * - Impossible de gérer plusieurs entretiens
     * - Logique métier éclatée
     *
     * 👉 Conservé temporairement pour compatibilité frontend.
     * 👉 À supprimer une fois le front totalement migré.
     */
    #[Route('/{id}/entretien', name: 'api_candidature_update_entretien', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateEntretienLegacy(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $candidature = $em->getRepository(Candidature::class)->find($id);

        if (!$candidature) {
            return $this->json(['error' => 'Candidature introuvable'], 404);
        }

        if ($candidature->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        try {
            // Ancienne logique : un seul entretien max
            if (array_key_exists('dateEntretien', $data)) {
                $candidature->setDateEntretien(
                    $data['dateEntretien']
                        ? new \DateTime($data['dateEntretien'])
                        : null
                );
            }

            if (array_key_exists('heureEntretien', $data)) {
                $candidature->setHeureEntretien(
                    $data['heureEntretien']
                        ? \DateTime::createFromFormat('H:i', $data['heureEntretien'])
                        : null
                );
            }

            $em->flush();

            return $this->json([
                'id' => $candidature->getId(),
                'message' => 'Endpoint legacy utilisé. Prévoir migration vers Entretien.',
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Format invalide'], 400);
        }
    }
}
