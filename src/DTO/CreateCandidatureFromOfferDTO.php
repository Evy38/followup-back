<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de validation pour la création d'une candidature depuis une offre Adzuna.
 *
 * Reçu par POST /api/candidatures/from-offer.
 * Validé par le Symfony Validator avant toute persistance.
 *
 * Champs obligatoires : externalId, company, redirectUrl
 * Champs optionnels  : title, location
 *
 * @see \App\Controller\Api\CandidatureFromOfferController
 */
class CreateCandidatureFromOfferDTO
{
  #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $externalId;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $company;

    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    public string $redirectUrl;

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Length(max: 255)]
    public ?string $location = null;
}
