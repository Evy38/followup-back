<?php

namespace App\DTO;

class JobOfferDTO
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $company,
        public string $location,
        public string $contractType,
        public ?int $salaryMin,
        public ?int $salaryMax,
        public string $redirectUrl
    ) {}
}
