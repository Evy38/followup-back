<?php

namespace App\Enum;

/**
 * Représente le résultat d'un entretien passé.
 * 
 * - ENGAGE : L'employeur a proposé un engagement/embauche
 * - NEGATIVE : Réponse négative de l'employeur
 * - ATTENTE : En attente d'une réponse
 */
enum ResultatEntretien: string
{
    case ENGAGE = 'engage';
    case NEGATIVE = 'negative';
    case ATTENTE = 'attente';
}