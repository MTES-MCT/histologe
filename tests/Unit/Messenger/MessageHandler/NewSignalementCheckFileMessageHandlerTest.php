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

class NewSignalementCheckFileMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testProcessNewSignalementCheckFileNotSent(): void
    {
        $handler = $this->checkSignalement('00000000-0000-0000-2024-000000000004');

        $suivi = $handler->suivi;
        $this->assertEmpty($suivi);
    }

    public function testProcessNewSignalementCheckFileSent(): void
    {
        $handler = $this->checkSignalement('00000000-0000-0000-2023-000000000027');

        $this->assertInstanceOf(Suivi::class, $handler->suivi);
        $this->assertStringContainsString('diagnostic', $handler->description);
    }

    private function checkSignalement(string $uuid): NewSignalementCheckFileMessageHandler
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => $uuid]);
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

        return $handler;
    }
}
