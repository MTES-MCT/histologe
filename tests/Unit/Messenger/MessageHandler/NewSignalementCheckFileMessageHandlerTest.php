<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Esabora;

use App\Entity\DesordreCritere;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Messenger\Message\NewSignalementCheckFileMessage;
use App\Messenger\MessageHandler\NewSignalementCheckFileMessageHandler;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class NewSignalementCheckFileMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testProcessNewSignalementCheckFileNotSent(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        /** @var LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $desordreCritereRepository = $this->entityManager->getRepository(DesordreCritere::class);
        /** @var SuiviManager $suiviManager */
        $suiviManager = $this->createMock(SuiviManager::class);
        /** @var Security $security */
        $security = $this->createMock(Security::class);
        $handler = new NewSignalementCheckFileMessageHandler(
            $signalementRepository,
            $loggerMock,
            $desordreCritereRepository,
            $suiviManager,
            $security,
        );
        $handler->__invoke($newSignalementCheckFileMessage);

        $suivi = $handler->suivi;
        $this->assertEmpty($suivi);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testProcessNewSignalementCheckFileSent(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        $newSignalementCheckFileMessage = new NewSignalementCheckFileMessage($signalement->getId());

        /** @var LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $desordreCritereRepository = $this->entityManager->getRepository(DesordreCritere::class);
        /** @var SuiviManager $suiviManager */
        $suiviManager = $this->createMock(SuiviManager::class);
        /** @var Security $security */
        $security = $this->createMock(Security::class);
        $handler = new NewSignalementCheckFileMessageHandler(
            $signalementRepository,
            $loggerMock,
            $desordreCritereRepository,
            $suiviManager,
            $security,
        );
        $handler->__invoke($newSignalementCheckFileMessage);

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringContainsString('diagnostic', $handler->description);
    }
}
