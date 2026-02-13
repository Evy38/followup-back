<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

class JobOfferDTO
{
    public function __construct(
        #[Groups(['job:read'])]
        public string $externalId,
        
        #[Groups(['job:read'])]
        public string $title,
        
        #[Groups(['job:read'])]
        public string $company,
        
        #[Groups(['job:read'])]
        public string $location,
        
        #[Groups(['job:read'])]
        public string $contractType,
        
        #[Groups(['job:read'])]
        public ?int $salaryMin,
        
        #[Groups(['job:read'])]
        public ?int $salaryMax,
        
        #[Groups(['job:read'])]
        public string $redirectUrl
    ) {}
}

