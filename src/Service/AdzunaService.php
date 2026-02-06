<?php

namespace App\Service;

use App\DTO\JobOfferDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'intégration avec l'API Adzuna.
 * 
 * Responsabilités :
 * - Recherche d'offres d'emploi via l'API Adzuna
 * - Transformation des données API en DTOs applicatifs
 * - Mapping des types de contrats en français
 * - Gestion des erreurs et fallback
 * 
 * Documentation API : https://developer.adzuna.com/docs/search
 */
class AdzunaService
{
    private const RESULTS_PER_PAGE = 100;
    private const MAX_PAGES = 5;
    private const API_BASE_URL = 'https://api.adzuna.com/v1/api/jobs';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $appId,
        private readonly string $appKey,
        private readonly string $country,
        private readonly ContractTypeMapper $contractMapper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Recherche des offres d'emploi avec pagination.
     * 
     * @param string $query Mots-clés de recherche (ex: "développeur PHP")
     * @param string $location Localisation (ex: "Paris", "France")
     * @param int $page Numéro de page (commence à 1)
     * @param string|null $contract Type de contrat (ex: "full_time", "permanent")
     * 
     * @return JobOfferDTO[] Tableau d'offres d'emploi
     * 
     * @throws ServiceUnavailableHttpException Si l'API Adzuna est indisponible
     */
    public function search(string $query, string $location, int $page = 1, ?string $contract = null): array
    {
        $params = [
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'what' => trim($query),
            'where' => trim($location),
            'results_per_page' => self::RESULTS_PER_PAGE,
        ];

        // Ajout du filtre de type de contrat si spécifié
        if ($contract !== null && $contract !== '') {
            $params['contract_time'] = $contract;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/%s/search/%d', self::API_BASE_URL, $this->country, $page),
                [
                    'query' => $params,
                    'timeout' => 10, // Timeout de 10 secondes
                ]
            );

            $data = $response->toArray();

            // Transformation des résultats en DTOs
            return array_map(
                fn(array $job) => $this->mapJobToDTO($job),
                $data['results'] ?? []
            );

        } catch (TransportExceptionInterface $e) {
            // Erreur réseau ou timeout
            $this->logger->error('Erreur de connexion à l\'API Adzuna', [
                'exception' => $e->getMessage(),
                'query' => $query,
                'location' => $location,
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Le service de recherche d\'emploi est temporairement indisponible. Veuillez réessayer ultérieurement.'
            );

        } catch (\Throwable $e) {
            // Erreur inattendue (parsing JSON, etc.)
            $this->logger->error('Erreur lors du traitement de la réponse Adzuna', [
                'exception' => $e->getMessage(),
                'query' => $query,
                'location' => $location,
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Une erreur est survenue lors de la recherche d\'emplois.'
            );
        }
    }

    /**
     * Récupère TOUTES les offres disponibles avec pagination automatique.
     * 
     * Limite de sécurité : Maximum 5 pages (500 résultats).
     * 
     * @param string $query Mots-clés de recherche
     * @param string $location Localisation
     * @param string|null $contract Type de contrat (optionnel)
     * 
     * @return JobOfferDTO[] Tableau complet des offres trouvées
     */
    public function searchAll(string $query, string $location, ?string $contract = null): array
    {
        $allJobs = [];
        $page = 1;

        while ($page <= self::MAX_PAGES) {
            $jobs = $this->search($query, $location, $page, $contract);

            // Si aucune offre retournée, on arrête la pagination
            if (empty($jobs)) {
                break;
            }

            $allJobs = array_merge($allJobs, $jobs);

            // Si moins de 100 résultats, c'est la dernière page
            if (count($jobs) < self::RESULTS_PER_PAGE) {
                break;
            }

            $page++;
        }

        return $allJobs;
    }

    /**
     * Transforme une offre brute de l'API Adzuna en DTO applicatif.
     * 
     * @param array $job Données brutes de l'API
     * @return JobOfferDTO DTO structuré
     */
    private function mapJobToDTO(array $job): JobOfferDTO
    {
        return new JobOfferDTO(
            externalId: (string) ($job['id'] ?? 'unknown'),
            title: $job['title'] ?? 'Titre non spécifié',
            company: $job['company']['display_name'] ?? 'Entreprise non spécifiée',
            location: $job['location']['display_name'] ?? 'Localisation non spécifiée',
            contractType: $this->contractMapper->toFrench(
                $this->extractContractType($job)
            ),
            salaryMin: $job['salary_min'] ?? null,
            salaryMax: $job['salary_max'] ?? null,
            redirectUrl: $job['redirect_url'] ?? ''
        );
    }

    /**
     * Extrait le type de contrat depuis les données Adzuna.
     * 
     * Gère les différentes variantes de noms de clés retournées par l'API.
     * 
     * @param array $job Données de l'offre
     * @return string|null Code du type de contrat (ex: "full_time", "permanent")
     */
    private function extractContractType(array $job): ?string
    {
        return $job['contract_time']
            ?? $job['contract_type']
            ?? $job['contractTime']
            ?? $job['contractType']
            ?? null;
    }
}