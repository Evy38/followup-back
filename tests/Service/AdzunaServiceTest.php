<?php

namespace App\Tests\Service;

use App\DTO\JobOfferDTO;
use App\Service\AdzunaService;
use App\Service\ContractTypeMapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AdzunaServiceTest extends TestCase
{
    public function testSearchReturnsJobOfferDTOArray(): void
    {
        $mockResponseData = [
            'results' => [
                [
                    'id' => '123',
                    'title' => 'Développeur PHP',
                    'company' => ['display_name' => 'TestCorp'],
                    'location' => ['display_name' => 'Paris'],
                    'contract_time' => 'full_time',
                    'salary_min' => 35000,
                    'salary_max' => 45000,
                    'redirect_url' => 'http://example.com/job/123',
                ],
            ],
        ];

        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($mockResponseData);
        $httpClient->method('request')->willReturn($response);

        $contractMapper = new ContractTypeMapper();
        $logger = $this->createMock(LoggerInterface::class);

        $service = new AdzunaService(
            $httpClient,
            'appid',
            'appkey',
            'fr',
            $contractMapper,
            $logger
        );

        $results = $service->search('php', 'paris');

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertInstanceOf(JobOfferDTO::class, $results[0]);
        $this->assertEquals('123', $results[0]->externalId);
        $this->assertEquals('Développeur PHP', $results[0]->title);
        $this->assertEquals('TestCorp', $results[0]->company);
        $this->assertEquals('Paris', $results[0]->location);
        $this->assertEquals('Temps plein', $results[0]->contractType); // Traduit en français
        $this->assertEquals(35000, $results[0]->salaryMin);
        $this->assertEquals(45000, $results[0]->salaryMax);
        $this->assertEquals('http://example.com/job/123', $results[0]->redirectUrl);
    }
}