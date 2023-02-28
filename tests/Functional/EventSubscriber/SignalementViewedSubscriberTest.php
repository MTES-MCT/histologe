<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementViewedEvent;
use App\EventSubscriber\SignalementViewedSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementViewedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementViewedEvent::NAME, SignalementViewedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementViewed(): void
    {
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'uuid' => '00000000-0000-0000-2023-000000000006',
        ]);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@histologe.fr']);
        $signalementViewedEvent = new SignalementViewedEvent($signalement, $user);

        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['signalement' => $signalement]);
        $isSeenBefore = $notification->getIsSeen();
        $this->assertFalse($isSeenBefore);

        $signalementViewedSubscriber = new SignalementViewedSubscriber($this->entityManager);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementViewedSubscriber);
        $dispatcher->dispatch($signalementViewedEvent, SignalementViewedEvent::NAME);

        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['signalement' => $signalement]);
        $isSeenAfter = $notification->getIsSeen();
        $this->assertTrue($isSeenAfter);
    }
}
