<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Esabora;

use App\Manager\AffectationManager;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Messenger\MessageHandler\Esabora\DossierMessageSCHSHandler;
use App\Messenger\MessageHandler\Esabora\DossierMessageSISHHandler;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Handler\DossierAdresseServiceHandler;
use App\Service\Interconnection\Esabora\Handler\DossierSISHHandlerInterface;
use App\Service\Interconnection\Esabora\Response\DossierPushSISHResponse;
use App\Tests\FixturesHelper;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DossierMessageHandlerTest extends TestCase
{
    use FixturesHelper;

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testProcessDossierMessageSCHS(): void
    {
        $faker = Factory::create();
        $dossierMessage = $this->getDossierMessageSCHS();
        $filepath = __DIR__.'/../../../../../tools/wiremock/src/Resources/Esabora/schs/ws_import.json';
        $mockResponse = new MockResponse((string) file_get_contents($filepath));
        $mockHttpClient = new MockHttpClient($mockResponse);
        $response = $mockHttpClient->request('POST', $faker->url());

        /** @var MockObject&EsaboraSCHSService $esaboraServiceMock */
        $esaboraServiceMock = $this->createMock(EsaboraSCHSService::class);
        $esaboraServiceMock
            ->expects($this->once())
            ->method('pushDossier')
            ->willReturn($response);

        /** @var MockObject&AffectationManager $affectationManagerMock */
        $affectationManagerMock = $this->createMock(AffectationManager::class);
        $affectationManagerMock
            ->expects($this->once())
            ->method('flagAsSynchronized')
            ->with($dossierMessage);

        $dossierMessageHandler = new DossierMessageSCHSHandler(
            $esaboraServiceMock,
            $affectationManagerMock
        );

        $dossierMessageHandler($dossierMessage);
    }

    public function testProcessDossierMessageSISH(): void
    {
        $dossierMessageSISH = new DossierMessageSISH();

        /** @var MockObject&DossierSISHHandlerInterface $dossierSISHHandlerMock */
        $dossierSISHHandlerMock = $this->createMock(DossierSISHHandlerInterface::class);
        $dossierSISHHandlerMock->expects($this->once())
            ->method('handle')
            ->with($dossierMessageSISH);
        $dossierSISHHandlerMock->expects($this->once())
            ->method('canFlagAsSynchronized')
            ->willReturn(true);

        /** @var MockObject&AffectationManager $affectationManagerMock */
        $affectationManagerMock = $this->createMock(AffectationManager::class);
        $affectationManagerMock->expects($this->once())
            ->method('flagAsSynchronized')
            ->with($dossierMessageSISH);

        $handler = new DossierMessageSISHHandler(['handler' => $dossierSISHHandlerMock], $affectationManagerMock);
        $handler($dossierMessageSISH);
    }

    public function testHandleReturnsFalseAndCanFlagAsSynchronizedWhenWsErrSql(): void
    {
        $esaboraSISHService = $this->createMock(EsaboraSISHService::class);
        $esaboraSISHService
            ->expects($this->once())
            ->method('pushAdresse')
            ->willReturn(
                new DossierPushSISHResponse(
                    [
                        'code' => 'WS_ERR_SQL',
                        'message' => '23505 - ERROR:  duplicate key value violates unique constraint',
                    ],
                    Response::HTTP_BAD_REQUEST
                )
            );

        $handler = new DossierAdresseServiceHandler($esaboraSISHService);
        $dossierMessageSISH = $this->getDossierMessageSISH();
        $isSuccessful = $handler->handle($dossierMessageSISH);

        self::assertFalse($isSuccessful);
        self::assertTrue($handler->canFlagAsSynchronized());
        self::assertTrue($handler->dossierAlreadyExists());
    }
}
