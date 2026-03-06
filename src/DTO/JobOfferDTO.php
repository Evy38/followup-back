<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DTO représentant une offre d'emploi retournée par l'API Adzuna.
 *
 * Utilisé comme objet de transfert entre AdzunaService et le JobController.
 * Sérialisé via le groupe `job:read` pour les réponses de l'API FollowUp.
 *
 * Le champ `contractType` est traduit en français par ContractTypeMapper.
 * Le champ `externalId` sert de référence pour créer une Candidature via
 * POST /api/candidatures/from-offer.
 *
 * @see \App\Service\AdzunaService
 * @see \App\Service\ContractTypeMapper
 */
class JobOfferDTO
{
    public function __construct(
        #[Groups(['job:read'])]
        public string $externalId,
        
        #[Groups(['job:read'])]
        public string $title,
        
        #[Groups(['job:read'])]
        public string $company,
        
        #[Groups(['job:read'])]
        public string $location,
        
        #[Groups(['job:read'])]
        public string $contractType,
        
        #[Groups(['job:read'])]
        public ?int $salaryMin,
        
        #[Groups(['job:read'])]
        public ?int $salaryMax,
        
        #[Groups(['job:read'])]
        public string $redirectUrl
    ) {}
}

