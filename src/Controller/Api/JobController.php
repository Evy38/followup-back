<?php

namespace App\Controller\Api;

use App\Service\AdzunaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Recherche d'offres d'emploi via l'API externe Adzuna.
 *
 * Endpoints :
 * - GET /api/jobs       Recherche paginée (infinite scroll) — max 5 pages
 * - GET /api/jobs/all   Récupère toutes les pages disponibles (max 10 pages)
 *
 * Paramètres query communs : `poste`, `ville`, `contrat` (optionnel)
 * Paramètre spécifique à /api/jobs : `page` (1 à 5)
 *
 * Les résultats sont mis en cache 2 heures (clé unique par combinaison poste/ville/page/contrat).
 * En cas d'indisponibilité du cache, l'API Adzuna est interrogée directement.
 *
 * @see \App\Service\AdzunaService
 */
#[Route('/api/jobs')]
class JobController extends AbstractController
{
    private const CACHE_TTL = 7200; // 2 heures en cache
    private const MAX_PAGES = 5;

    public function __construct(
        private readonly AdzunaService $adzunaService,
        private readonly CacheInterface $cache
    ) {}

    #[Route('', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $ville = $request->query->get('ville', 'france');
        $poste = $request->query->get('poste', 'developer');
        $page = max(1, min((int) $request->query->get('page', 1), self::MAX_PAGES));
        $contrat = $request->query->get('contrat');
        
        // Clé de cache unique par recherche (page par page)
        $cacheKey = 'jobs_' . md5("{$poste}_{$ville}_{$page}_" . ($contrat ?? ''));
        
        try {
            $jobs = $this->cache->get($cacheKey, function() use ($poste, $ville, $page, $contrat) {
                return $this->adzunaService->search($poste, $ville, $page, $contrat);
            });
        } catch (\Throwable $e) {
            // En cas d'erreur cache, appel direct à l'API
            $jobs = $this->adzunaService->search($poste, $ville, $page, $contrat);
        }
        
        // Réponse avec metadata de pagination pour infinite scroll
        $hasMore = count($jobs) >= 50;  // 50 = RESULTS_PER_PAGE
        
        return $this->json([
            'data' => $jobs,
            'pagination' => [
                'page' => $page,
                'pageSize' => count($jobs),
                'hasMore' => $hasMore,
                'nextPage' => $hasMore ? $page + 1 : null,
            ]
        ], Response::HTTP_OK, [], ['groups' => ['job:read']]
        )->setSharedMaxAge(self::CACHE_TTL);
    }

    #[Route('/all', methods: ['GET'])]
    public function searchAll(Request $request): JsonResponse
    {
        $ville = $request->query->get('ville', 'france');
        $poste = $request->query->get('poste', 'developer');
        $contrat = $request->query->get('contrat');
        
        // Clé de cache unique pour recherche complète
        $cacheKey = 'jobs_all_' . md5("{$poste}_{$ville}_" . ($contrat ?? ''));
        
        try {
            $jobs = $this->cache->get($cacheKey, function() use ($poste, $ville, $contrat) {
                return $this->adzunaService->searchAll($poste, $ville, $contrat);
            });
        } catch (\Throwable $e) {
            $jobs = $this->adzunaService->searchAll($poste, $ville, $contrat);
        }
        
        return $this->json(
            $jobs,
            Response::HTTP_OK,
            [],
            ['groups' => ['job:read']]
        )->setSharedMaxAge(self::CACHE_TTL);
    }
}