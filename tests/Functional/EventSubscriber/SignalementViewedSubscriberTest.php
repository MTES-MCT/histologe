<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementViewedEvent;
use App\EventSubscriber\SignalementViewedSubscriber;
use App\Manager\SignalementManager;
use App\Service\DataGouv\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementViewedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AddressService $addressService;
    private SignalementManager $signalementManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->addressService = static::getContainer()->get(AddressService::class);
        $this->signalementManager = static::getContainer()->get(SignalementManager::class);
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementViewedEvent::NAME, SignalementViewedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementViewed(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'uuid' => '00000000-0000-0000-2023-000000000006',
        ]);

        // Empty this data voluntarily to check if the dispatcher handles it.
        $signalement
            ->setInseeOccupant(null)
            ->setGeoloc([])
            ->setCpOccupant(null);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@histologe.fr']);
        $signalementViewedEvent = new SignalementViewedEvent($signalement, $user);

        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['signalement' => $signalement]);
        $isSeenBefore = $notification->getIsSeen();
        $this->assertFalse($isSeenBefore);

        $signalementViewedSubscriber = new SignalementViewedSubscriber(
            $this->entityManager,
            $this->addressService,
            $this->signalementManager
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementViewedSubscriber);
        $dispatcher->dispatch($signalementViewedEvent, SignalementViewedEvent::NAME);

        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['signalement' => $signalement]);
        $isSeenAfter = $notification->getIsSeen();
        $this->assertTrue($isSeenAfter);

        $this->assertEquals('13203', $signalement->getInseeOccupant());
        $this->assertArrayHasKey('lat', $signalement->getGeoloc());
        $this->assertArrayHasKey('lng', $signalement->getGeoloc());
        $this->assertEquals('13003', $signalement->getCpOccupant());
    }
}
