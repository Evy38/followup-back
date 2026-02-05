<?php

namespace App\Service;

use App\DTO\JobOfferDTO;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdzunaService
{
    private const RESULTS_PER_PAGE = 100;
    private const MAX_PAGES = 5; 

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $appId,
        private string $appKey,
        private string $country,
        private ContractTypeMapper $contractMapper
    ) {}

    /**
     * Extrait le type de contrat depuis les données Adzuna
     * Adzuna peut retourner cette info sous différents noms de clés
     */
    private function extractContractType(array $job): ?string
    {
        // Essayons toutes les variantes possibles
        return $job['contract_time'] 
            ?? $job['contract_type'] 
            ?? $job['contractTime'] 
            ?? $job['contractType']
            ?? null;
    }

    /**
     * @return JobOfferDTO[]
     */
    public function search(string $query, string $location, int $page = 1, ?string $contract = null): array
    {
        $params = [
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'what' => $query,
            'where' => $location,
            'results_per_page' => self::RESULTS_PER_PAGE,
        ];
        
        if ($contract) {
            $params['contract_time'] = $contract;
        }

        $response = $this->httpClient->request(
            'GET',
            "https://api.adzuna.com/v1/api/jobs/{$this->country}/search/{$page}",
            ['query' => $params]
        );

        $data = $response->toArray();

        return array_map(
            fn ($job) => new JobOfferDTO(
                externalId: (string) $job['id'],
                title: $job['title'],
                company: $job['company']['display_name'] ?? 'N/A',
                location: $job['location']['display_name'] ?? 'N/A',
                // ✅ Extraction robuste + transformation en français
                contractType: $this->contractMapper->toFrench(
                    $this->extractContractType($job)
                ),
                salaryMin: $job['salary_min'] ?? null,
                salaryMax: $job['salary_max'] ?? null,
                redirectUrl: $job['redirect_url']
            ),
            $data['results']
        );
    }

    /**
     * Récupère TOUTES les offres disponibles (avec limite de sécurité)
     * @return JobOfferDTO[]
     */
    public function searchAll(string $query, string $location, ?string $contract = null): array
    {
        $allJobs = [];
        $page = 1;

        while ($page <= self::MAX_PAGES) {
            $jobs = $this->search($query, $location, $page, $contract);
            
            // Si aucune offre retournée, on arrête
            if (empty($jobs)) {
                break;
            }

            $allJobs = array_merge($allJobs, $jobs);
            
            // Si on a récupéré moins de 100 résultats, c'est la dernière page
            if (count($jobs) < self::RESULTS_PER_PAGE) {
                break;
            }

            $page++;
        }

        return $allJobs;
    }
}