<?php

namespace App\Service;

/**
 * Service de mapping des types de contrats Adzuna vers les libellés français
 * 
 * Ce service transforme les codes anglais de l'API Adzuna en termes compréhensibles
 * pour les utilisateurs francophones.
 * 
 * Responsabilité : Centraliser la logique de transformation des types de contrats
 */
class ContractTypeMapper
{
    /**
     * Mapping des codes Adzuna vers les libellés français
     * Basé sur la documentation : https://developer.adzuna.com/docs/search
     */
    private const CONTRACT_MAPPING = [
        'full_time' => 'Temps plein',
        'part_time' => 'Temps partiel',
        'contract' => 'CDD',
        'permanent' => 'CDI',
        'temporary' => 'Intérim',
        'freelance' => 'Freelance',
        'internship' => 'Stage',
        'apprenticeship' => 'Alternance',
        'volunteer' => 'Bénévolat',
    ];

    /**
     * Transforme un code de type de contrat en libellé français
     * 
     * @param string|null $contractCode Le code retourné par l'API Adzuna
     * @return string Le libellé en français ou "Non spécifié" si inconnu
     * 
     * Exemples :
     * - "full_time" → "Temps plein"
     * - "permanent" → "CDI"
     * - null → "Non spécifié"
     * - "unknown_type" → "Non spécifié"
     */
    public function toFrench(?string $contractCode): string
    {
        // Si le code est null ou vide, retourne "Non spécifié"
        if (empty($contractCode)) {
            return 'Non spécifié';
        }

        // Retourne le libellé français si trouvé, sinon "Non spécifié"
        return self::CONTRACT_MAPPING[$contractCode] ?? 'Non spécifié';
    }

    /**
     * Retourne tous les types de contrats disponibles
     * Utile pour générer des filtres ou des listes déroulantes
     * 
     * @return array<string, string> Tableau associatif [code => libellé]
     */
    public function getAllTypes(): array
    {
        return self::CONTRACT_MAPPING;
    }
}