<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Event\SignalementViewedEvent;
use App\EventSubscriber\SignalementViewedSubscriber;
use App\Manager\SignalementManager;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\Address;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementViewedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementManager $signalementManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
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

        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy(['signalement' => $signalement]);
        $isSeenBefore = $notification->getIsSeen();
        $this->assertFalse($isSeenBefore);

        $user = $notification->getUser();
        $signalementViewedEvent = new SignalementViewedEvent($signalement, $user);

        $addressResult = json_decode(file_get_contents(__DIR__.'/../../files/datagouv/get_api_ban_item_response_13203.json'), true);
        $address = new Address($addressResult);
        /** @var MockObject&AddressService $addressServiceMock */
        $addressServiceMock = $this->createMock(AddressService::class);
        $this->signalementManager = static::getContainer()->get(SignalementManager::class);

        $addressServiceMock
            ->expects($this->once())
            ->method('getAddress')
            ->willReturn($address);

        $signalementViewedSubscriber = new SignalementViewedSubscriber(
            $this->entityManager,
            $addressServiceMock,
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
        $this->assertEquals([], $signalement->getGeoloc());
        $this->assertEquals('13003', $signalement->getCpOccupant());
    }
}
