<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Relance;

class RelanceService
{
    /**
     * Génère 3 relances (S+1 / S+2 / S+3) à partir de la date de candidature.
     */
    public function createDefaultRelances(Candidature $candidature): array
    {
        $dateBase = $candidature->getDateCandidature();

        if (!$dateBase) {
            $dateBase = new \DateTimeImmutable();
        }

        if ($dateBase instanceof \DateTime) {
            $dateBase = \DateTimeImmutable::createFromMutable($dateBase);
        }

        $offsets = [
            1 => 7,
            2 => 14,
            3 => 21,
        ];

        $relances = [];

        foreach ($offsets as $rang => $days) {
            $r = new Relance();
            $r->setCandidature($candidature);
            $r->setRang($rang);
            $r->setDateRelance($dateBase->modify("+{$days} days"));
            $r->setFaite(false);
            $r->setDateRealisation(null);

            // Optionnel : type lisible pour toi
            $r->setType("S+{$rang}");

            $relances[] = $r;
        }

        return $relances;
    }
}
