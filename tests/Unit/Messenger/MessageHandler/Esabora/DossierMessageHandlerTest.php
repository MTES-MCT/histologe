<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Esabora;

use App\Entity\Partner;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Messenger\MessageHandler\Esabora\DossierMessageSCHSHandler;
use App\Repository\PartnerRepository;
use App\Service\Esabora\EsaboraSCHSService;
use App\Tests\FixturesHelper;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DossierMessageHandlerTest extends TestCase
{
    use FixturesHelper;

    /**
     * @throws TransportExceptionInterface
     */
    public function testProcessDossierMessage(): void
    {
        $faker = Factory::create();
        $dossierMessage = $this->getDossierMessageSCHS();
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/schs/ws_import.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));
        $mockHttpClient = new MockHttpClient($mockResponse);
        $response = $mockHttpClient->request('POST', $faker->url());

        $esaboraServiceMock = $this->createMock(EsaboraSCHSService::class);
        $esaboraServiceMock
            ->expects($this->once())
            ->method('pushDossier')
            ->willReturn($response);

        $jobEventManagerMock = $this->createMock(JobEventManager::class);

        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($dossierMessage, 'json')
            ->willReturn(json_encode([]));

        $partnerRepositoryMock = $this->createMock(PartnerRepository::class);
        $partnerRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with($dossierMessage->getPartnerId())
            ->willReturn(new Partner());

        $affectationManagerMock = $this->createMock(AffectationManager::class);
        $affectationManagerMock
            ->expects($this->once())
            ->method('flagAsSynchronized')
            ->with($dossierMessage);

        $dossierMessageHandler = new DossierMessageSCHSHandler(
            $esaboraServiceMock,
            $jobEventManagerMock,
            $serializerMock,
            $partnerRepositoryMock,
            $affectationManagerMock
        );

        $dossierMessageHandler($dossierMessage);
    }
}
