<?php

namespace App\Enum;

/**
 * Représente le statut d'avancement d'un entretien.
 * 
 * - PREVU : L'entretien est planifié mais n'a pas encore eu lieu
 * - PASSE : L'entretien a eu lieu
 */
enum StatutEntretien: string
{
    case PREVU = 'prevu';
    case PASSE = 'passe';
}