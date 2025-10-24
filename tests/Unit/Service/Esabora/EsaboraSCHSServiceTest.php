<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Entity\Enum\PartnerType;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use App\Service\UploadHandlerService;
use App\Tests\FileHelper;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EsaboraSCHSServiceTest extends KernelTestCase
{
    use FileHelper;
    use FixturesHelper;

    public const PATH_RESOURCE_JSON = '/../../../../tools/wiremock/src/Resources/Esabora/schs/';

    private MockObject&UploadHandlerService $uploadHandlerService;
    private MockObject&LoggerInterface $logger;
    private ?string $tempFilepath;

    protected function setUp(): void
    {
        $this->tempFilepath = $this->getTempFilepath();
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testPushDossierToEsaboraSasSuccess(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_import.json';
        $mockResponse = new MockResponse((string) file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('getTmpFilepath')
            ->willReturn($this->tempFilepath);

        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $response = $esaboraService->pushDossier($this->getDossierMessageSCHS());

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('insert', (string) $response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testPushDossierToEsaboraSasFailed(): void
    {
        $mockResponse = new MockResponse([], ['http_code' => Response::HTTP_INTERNAL_SERVER_ERROR]);
        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('getTmpFilepath')
            ->willReturn($this->tempFilepath);
        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $response = $esaboraService->pushDossier($this->getDossierMessageSCHS());

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testGetStateDossierFromEsaboraSas(): void
    {
        $filepath = __DIR__.self::PATH_RESOURCE_JSON.'ws_etat_dossier_sas/etat_importe.json';
        $mockResponse = new MockResponse((string) file_get_contents($filepath));

        $mockHttpClient = new MockHttpClient($mockResponse);
        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $affectation = $this->getAffectation(PartnerType::COMMUNE_SCHS);
        $dossierResponse = $esaboraService->getStateDossier(
            $affectation,
            $affectation->getSignalement()->getUuid()
        );

        $this->assertInstanceOf(DossierStateSCHSResponse::class, $dossierResponse);
        $this->assertEquals('00000000-0000-0000-2022-000000000001', $dossierResponse->getSasReference());
        $this->assertEquals('Importé', $dossierResponse->getSasEtat());
        $this->assertEquals(Response::HTTP_OK, $dossierResponse->getStatusCode());
        $this->assertEquals('en cours', $dossierResponse->getEtat());
    }

    public function testGetStateDossierFromEsaboraThrownException(): void
    {
        $mockHttpClient = new MockHttpClient(function () {
            throw new TransportException();
        });

        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $affectation = $this->getAffectation(PartnerType::COMMUNE_SCHS);
        $dossierResponse = $esaboraService->getStateDossier(
            $affectation,
            $affectation->getSignalement()->getUuid()
        );
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $dossierResponse->getStatusCode());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testPushDossierToEsaboraThrownException(): void
    {
        $mockHttpClient = new MockHttpClient(function () {
            throw new TransportException();
        });

        $this->uploadHandlerService
            ->expects($this->atLeast(1))
            ->method('getTmpFilepath')
            ->willReturn($this->tempFilepath);
        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $response = $esaboraService->pushDossier($this->getDossierMessageSCHS());
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testGetDossierEventsEsaboraSas(): void
    {
        $filepathEvents = __DIR__.self::PATH_RESOURCE_JSON.'ws_get_dossier_events.json';
        $mockResponse = new MockResponse((string) file_get_contents($filepathEvents));
        $filepathEventFiles = __DIR__.self::PATH_RESOURCE_JSON.'ws_get_dossier_event_files.json';
        $mockResponseEventFiles = new MockResponse((string) file_get_contents($filepathEventFiles));

        $mockHttpClient = new MockHttpClient([$mockResponse, $mockResponseEventFiles]);
        $esaboraService = new EsaboraSCHSService($mockHttpClient, $this->logger, $this->uploadHandlerService);
        $affectation = $this->getAffectation(PartnerType::COMMUNE_SCHS);
        $dossierEvents = $esaboraService->getDossierEvents(
            $affectation,
            $affectation->getSignalement()->getUuid(),
        );
        $this->assertEquals('27207', $dossierEvents->getSearchId());
        $affectation = $this->getAffectation(PartnerType::COMMUNE_SCHS);

        $dossierEventFiles = $esaboraService->getDossierEventFiles($affectation, $dossierEvents->getCollection()[0]);
        $this->assertNotEmpty($dossierEventFiles->getDocumentZipContent());
    }

    protected function tearDown(): void
    {
        unlink($this->tempFilepath);
        $this->tempFilepath = null;
    }
}
