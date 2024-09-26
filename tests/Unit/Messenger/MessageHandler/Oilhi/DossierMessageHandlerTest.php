<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Oilhi;

use App\Manager\AffectationManager;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Messenger\MessageHandler\Oilhi\DossierMessageHandler;
use App\Service\Interconnection\Oilhi\HookZapierService;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DossierMessageHandlerTest extends TestCase
{
    /**
     * @throws ExceptionInterface
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

        $affectationManagerMock = $this->createMock(AffectationManager::class);
        $affectationManagerMock
            ->expects($this->once())
            ->method('flagAsSynchronized')
            ->with($dossierMessage);

        $dossierMessageHandler = new DossierMessageHandler(
            $hookZapierServiceMock,
            $affectationManagerMock
        );

        $dossierMessageHandler($dossierMessage);
    }
}
