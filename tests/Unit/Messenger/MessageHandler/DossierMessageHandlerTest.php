<?php

namespace App\Tests\Unit\Messenger\MessageHandler;

use App\Manager\JobEventManager;
use App\Messenger\MessageHandler\DossierMessageHandler;
use App\Service\Esabora\EsaboraService;
use App\Tests\Unit\Messenger\DossierMessageTrait;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DossierMessageHandlerTest extends TestCase
{
    use DossierMessageTrait;

    /**
     * @throws TransportExceptionInterface
     */
    public function testProcessDossierMessage(): void
    {
        $faker = Factory::create();
        $dossierMessage = $this->getDossierMessage();
        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/ws_import.json';
        $mockResponse = new MockResponse(file_get_contents($filepath));
        $mockHttpClient = new MockHttpClient($mockResponse);
        $response = $mockHttpClient->request('POST', $faker->url());

        $esaboraServiceMock = $this->createMock(EsaboraService::class);
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

        $dossierMessageHandler = new DossierMessageHandler(
            $esaboraServiceMock,
            $jobEventManagerMock,
            $serializerMock
        );

        $dossierMessageHandler($dossierMessage);
    }
}
