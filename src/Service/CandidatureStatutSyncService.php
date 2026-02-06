<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Entretien;
use App\Enum\ResultatEntretien;
use App\Enum\StatutEntretien;
use App\Enum\StatutReponse;

/**
 * Service de synchronisation du statut de réponse d'une candidature
 * en fonction de l'état de ses entretiens.
 * 
 * Logique métier :
 * - Si entretien passé avec résultat "engagé" → statutReponse = "engage"
 * - Si entretien passé avec résultat "négatif" → statutReponse = "negative"
 * - Si au moins un entretien existe (quel que soit son état) → statutReponse = "entretien"
 * - Si aucun entretien → statutReponse = "attente"
 */
class CandidatureStatutSyncService
{
    /**
     * Synchronise le statut de réponse de la candidature en fonction
     * de l'état de ses entretiens.
     * 
     * @param Candidature $candidature La candidature à synchroniser
     */
    public function syncFromEntretien(Candidature $candidature): void
    {
        $entretiens = $candidature->getEntretiens();

        // Aucun entretien → on ne modifie pas le statut ici
        if ($entretiens->isEmpty()) {
            return;
        }

        // Récupère le dernier entretien (logique simple et suffisante)
        /** @var Entretien $lastEntretien */
        $lastEntretien = $entretiens->last();

        // Si l'entretien est passé, on regarde le résultat
        if ($lastEntretien->getStatut() === StatutEntretien::PASSE) {
            $resultat = $lastEntretien->getResultat();

            if ($resultat === ResultatEntretien::ENGAGE) {
                $candidature->setStatutReponse(StatutReponse::ENGAGE);
                return;
            }

            if ($resultat === ResultatEntretien::NEGATIVE) {
                $candidature->setStatutReponse(StatutReponse::NEGATIVE);
                return;
            }
        }

        // Par défaut, s'il existe au moins un entretien (prévu ou passé)
        $candidature->setStatutReponse(StatutReponse::ENTRETIEN);
    }

    /**
     * Réinitialise le statut de réponse après suppression de tous les entretiens.
     * 
     * @param Candidature $candidature La candidature à réinitialiser
     */
    public function syncAfterEntretienDeletion(Candidature $candidature): void
    {
        if ($candidature->getEntretiens()->isEmpty()) {
            $candidature->setStatutReponse(StatutReponse::ATTENTE);
        }
    }
}