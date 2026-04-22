<?php

namespace App\Enum;

/**
 * Représente le statut de réponse d'une candidature.
 *
 * - ATTENTE    : Aucune réponse reçue (état par défaut)
 * - ECHANGES   : Des échanges sont en cours avec l'entreprise
 * - ENTRETIEN  : Un entretien a été planifié ou réalisé
 * - NEGATIVE   : Réponse négative reçue
 * - ENGAGE     : Retour positif / Offre d'embauche
 *
 * Note : ANNULE a été supprimé — les candidatures sans suite
 * sont désormais archivées via le champ `archivedAt`.
 */
enum StatutReponse: string
{
    case ATTENTE   = 'attente';
    case ECHANGES  = 'echanges';
    case ENTRETIEN = 'entretien';
    case NEGATIVE  = 'negative';
    case ENGAGE    = 'engage';
}