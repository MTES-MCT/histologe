<?php

namespace App\Tests\Unit\Service\Oilhi;

use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\Interconnection\Oilhi\HookZapierService;
use App\Service\Interconnection\Oilhi\Model\Desordre;
use Faker\Factory;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HookZapierServiceTest extends TestCase
{
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ExceptionInterface
     */
    public function testPushDossierWithSuccess(): void
    {
        $faker = Factory::create();
        $response = [
            'attempt' => '00000000-0000-0000-0000-000000000001',
            'id' => '00000000-0000-0000-0000-000000000002',
            'request_id' => '00000000-0000-0000-0000-000000000003',
            'status' => 'success',
        ];
        $mockResponse = new MockResponse($response);

        $serializer = new Serializer([new ObjectNormalizer()]);
        $mockHttpClient = new MockHttpClient($mockResponse);
        $hookZapierService = new HookZapierService(
            $mockHttpClient,
            $this->logger,
            $serializer,
            'ZAPIER_OILHI_TOKEN',
            'USER_ID',
            'ZAP_ID',
        );

        $dossierMessage = (new DossierMessage())
            ->setAction('push_dossier')
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setDesordres([
                new Desordre(
                    'categorie',
                    'equipement',
                    'risque',
                    true,
                    false,
                    true)]
            )
            ->setSignalementUrl($faker->url());

        $response = $hookZapierService->pushDossier($dossierMessage);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
