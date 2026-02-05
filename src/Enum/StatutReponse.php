<?php

namespace App\Enum;

/**
 * Représente le statut de réponse d'une candidature.
 * 
 * - ATTENTE : Aucune réponse reçue
 * - ECHANGES : Des échanges sont en cours avec l'entreprise
 * - ENTRETIEN : Un entretien a été planifié ou réalisé
 * - NEGATIVE : Réponse négative reçue
 * - ENGAGE : Retour positif / Offre d'embauche
 * - ANNULE : L'entretien ou le processus a été annulé
 */
enum StatutReponse: string
{
    case ATTENTE = 'attente';
    case ECHANGES = 'echanges';
    case ENTRETIEN = 'entretien';
    case NEGATIVE = 'negative';
    case ENGAGE = 'engage';
    case ANNULE = 'annule';
}