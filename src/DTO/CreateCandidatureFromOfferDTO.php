<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

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
