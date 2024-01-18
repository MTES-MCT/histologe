<?php

namespace App\Tests\Unit\Service\Oilhi;

use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\Oilhi\HookZapierService;
use Faker\Factory;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class HookZapierServiceTest extends TestCase
{
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testPushDossierWithSuccess()
    {
        $faker = Factory::create();
        $response = [
            'attempt' => '00000000-0000-0000-0000-000000000001',
            'id' => '00000000-0000-0000-0000-000000000002',
            'request_id' => '00000000-0000-0000-0000-000000000003',
            'status' => 'success',
        ];
        $mockResponse = new MockResponse($response);

        $normalizer = new ObjectNormalizer();
        $mockHttpClient = new MockHttpClient($mockResponse);
        $hookZapierService = new HookZapierService(
            $mockHttpClient,
            $this->logger,
            $normalizer,
            'ZAPIER_OILHI_TOKEN',
            'USER_ID',
            'ZAP_ID',
        );

        $dossierMessage = (new DossierMessage())
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setSignalementUrl($faker->url());

        $response = $hookZapierService->pushDossier($dossierMessage);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
