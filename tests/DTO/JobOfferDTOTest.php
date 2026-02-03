<?php

namespace App\Tests\DTO;

use App\DTO\JobOfferDTO;
use PHPUnit\Framework\TestCase;

class JobOfferDTOTest extends TestCase
{
    public function testJobOfferDTOProperties()
    {
        $dto = new JobOfferDTO(
            externalId: '123',
            title: 'Développeur PHP',
            company: 'ACME',
            location: 'Paris',
            contractType: 'CDI',
            salaryMin: 35000,
            salaryMax: 45000,
            redirectUrl: 'https://acme.com/job/123'
        );

        $this->assertEquals('123', $dto->externalId);
        $this->assertEquals('Développeur PHP', $dto->title);
        $this->assertEquals('ACME', $dto->company);
        $this->assertEquals('Paris', $dto->location);
        $this->assertEquals('CDI', $dto->contractType);
        $this->assertEquals(35000, $dto->salaryMin);
        $this->assertEquals(45000, $dto->salaryMax);
        $this->assertEquals('https://acme.com/job/123', $dto->redirectUrl);
    }
}
