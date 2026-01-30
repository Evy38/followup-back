<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Entretien;

class CandidatureStatutSyncService
{
    public function syncFromEntretien(Candidature $candidature): void
    {
        $entretiens = $candidature->getEntretiens();

        // Aucun entretien => ne pas forcer le statut ici
        if ($entretiens->isEmpty()) {
            return;
        }

        // On prend le dernier entretien (logique simple et suffisante)
        /** @var Entretien $lastEntretien */
        $lastEntretien = $entretiens->last();

        if ($lastEntretien->getStatut() === 'passe') {
            if ($lastEntretien->getResultat() === 'positive') {
                $candidature->setStatutReponse('positive');
                return;
            }

            if ($lastEntretien->getResultat() === 'negative') {
                $candidature->setStatutReponse('negative');
                return;
            }
        }

        // Par défaut, s’il existe au moins un entretien
        $candidature->setStatutReponse('entretien');
    }

    public function syncAfterEntretienDeletion(Candidature $candidature): void
    {
        if ($candidature->getEntretiens()->isEmpty()) {
            $candidature->setStatutReponse('annule');
        }
    }
}
