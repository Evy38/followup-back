<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Relance;

/**
 * Service de gestion des relances automatiques.
 * 
 * Génère un plan de relance par défaut (J+7, J+14, J+21)
 * pour chaque nouvelle candidature.
 * 
 * Règle métier : Les relances suivent un calendrier fixe pour
 * encourager le suivi régulier des candidatures.
 */
class RelanceService
{
    /**
     * Génère 3 relances par défaut à partir de la date de candidature.
     * 
     * Planning automatique :
     * - Relance 1 (S+1) : J+7 (1 semaine après la candidature)
     * - Relance 2 (S+2) : J+14 (2 semaines après)
     * - Relance 3 (S+3) : J+21 (3 semaines après)
     * 
     * @param Candidature $candidature La candidature pour laquelle générer les relances
     * 
     * @return Relance[] Tableau des 3 relances créées (non persistées en base)
     * 
     */
    public function createDefaultRelances(Candidature $candidature): array
    {
        $dateBase = $candidature->getDateCandidature();

        // Fallback : si aucune date de candidature, utiliser maintenant
        if (!$dateBase) {
            $dateBase = new \DateTimeImmutable();
        }

        // Conversion DateTime → DateTimeImmutable si nécessaire
        if ($dateBase instanceof \DateTime) {
            $dateBase = \DateTimeImmutable::createFromMutable($dateBase);
        }

        // Configuration du planning de relances
        $planning = [
            1 => 7,   // Relance 1 : J+7
            2 => 14,  // Relance 2 : J+14
            3 => 21,  // Relance 3 : J+21
        ];

        $relances = [];

        foreach ($planning as $rang => $joursApres) {
            $relance = new Relance();
            $relance->setCandidature($candidature);
            $relance->setRang($rang);
            $relance->setDateRelance($dateBase->modify("+{$joursApres} days"));
            $relance->setFaite(false);
            $relance->setDateRealisation(null);
            $relance->setType("S+{$rang}"); // Libellé : "S+1", "S+2", "S+3"

            $relances[] = $relance;
        }

        return $relances;
    }
}