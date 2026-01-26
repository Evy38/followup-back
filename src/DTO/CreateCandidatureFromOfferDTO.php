<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCandidatureFromOfferDTO
{
    #[Assert\NotBlank]
    public string $externalId;

    #[Assert\NotBlank]
    public string $company;

    #[Assert\NotBlank]
    public string $redirectUrl;

    public ?string $title = null;
    public ?string $location = null;
}
