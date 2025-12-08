<?php

namespace App\Tests\Functional\Messenger\MessageHandler;

use App\Entity\Enum\SignalementStatus;
use App\Messenger\Message\SignalementDraftProcessMessage;
use App\Messenger\MessageHandler\SignalementAddressUpdateAndAutoAssignMessageHandler;
use App\Repository\AffectationRepository;
use App\Repository\NotificationRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

class SignalementAddressUpdateAndAutoAssignMessageHandlerTest extends WebTestCase
{
    public function testWithoutAutoAffectation(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2024-07']);

        $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(0, $notifications);

        $message = new SignalementDraftProcessMessage($signalement->getCreatedFrom()?->getId(), $signalement->getId());
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(SignalementAddressUpdateAndAutoAssignMessageHandler::class);
        $handler($message);

        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(4, $notifications);

        $this->assertEmailCount(0);
    }

    public function testWithAutoAffectation(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-05']);

        $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(0, $notifications);

        $affectationRepository = static::getContainer()->get(AffectationRepository::class);
        $affectations = $affectationRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(0, $affectations);

        $message = new SignalementDraftProcessMessage($signalement->getCreatedFrom()?->getId(), $signalement->getId());
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(SignalementAddressUpdateAndAutoAssignMessageHandler::class);
        $handler($message);

        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(0, $notifications);

        $affectations = $affectationRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(2, $affectations);

        $this->assertEmailCount(3);
    }

    public function testWithInjonctionBailleur(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $messageBus = $container->get(MessageBusInterface::class);
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-05']);
        $signalement->setStatut(SignalementStatus::INJONCTION_BAILLEUR);
        $signalementRepository->save($signalement, true);

        $notificationRepository = static::getContainer()->get(NotificationRepository::class);
        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(0, $notifications);

        $affectationRepository = static::getContainer()->get(AffectationRepository::class);
        $affectations = $affectationRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(0, $affectations);

        $message = new SignalementDraftProcessMessage($signalement->getCreatedFrom()?->getId(), $signalement->getId());
        $messageBus->dispatch($message);
        $transport = $container->get('messenger.transport.async_priority_high');
        $envelopes = $transport->get();

        $this->assertCount(1, $envelopes);
        $handler = $container->get(SignalementAddressUpdateAndAutoAssignMessageHandler::class);
        $handler($message);

        $notifications = $notificationRepository->findBy(['signalement' => $signalement, 'type' => 'NOUVEAU_SIGNALEMENT']);
        $this->assertCount(0, $notifications);

        $affectations = $affectationRepository->findBy(['signalement' => $signalement]);
        $this->assertCount(0, $affectations);

        $this->assertEmailCount(1);
    }
}
