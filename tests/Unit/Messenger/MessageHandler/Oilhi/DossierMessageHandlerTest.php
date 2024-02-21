<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Oilhi;

use App\Entity\Partner;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Messenger\MessageHandler\Oilhi\DossierMessageHandler;
use App\Repository\PartnerRepository;
use App\Service\Oilhi\HookZapierService;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DossierMessageHandlerTest extends TestCase
{
    /**
     * @throws TransportExceptionInterface
     */
    public function testProcessDossierMessage(): void
    {
        $faker = Factory::create();
        $dossierMessage = (new DossierMessage())
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setSignalementUrl($faker->url());

        $response = [
            'attempt' => '00000000-0000-0000-0000-5cac94ca475b',
            'id' => '018c01fd-500e-b8c7-8668-5cac94ca475b',
            'request_id' => '018c01fd-500e-b8c7-8668-5cac94ca475b',
            'status' => 'success',
        ];
        $mockResponse = new MockResponse($response);

        $mockHttpClient = new MockHttpClient($mockResponse);
        $response = $mockHttpClient->request('POST', $faker->url());

        $hookZapierServiceMock = $this->createMock(HookZapierService::class);
        $hookZapierServiceMock
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

        $dossierMessageHandler = new DossierMessageHandler(
            $serializerMock,
            $jobEventManagerMock,
            $hookZapierServiceMock,
            $partnerRepositoryMock,
            $affectationManagerMock
        );

        $dossierMessageHandler($dossierMessage);
    }
}
