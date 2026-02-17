<?php

namespace App\Enum;

/**
 * Enum des statuts de candidature (table Statut/libellé)
 */
enum StatutCandidature: string
{
    case ENVOYEE = 'Envoyée';
    case EN_COURS = 'En cours';
    case RELANCEE = 'Relancée';
    case ENTRETIEN = 'Entretien';
    case REFUSEE = 'Refusée';
    case ACCEPTEE = 'Acceptée';
}
