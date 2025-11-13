<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Event\SignalementViewedEvent;
use App\Event\SuiviViewedEvent;
use App\EventSubscriber\SignalementViewedSubscriber;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Security\User\SignalementUser;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\Address;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementViewedSubscriberTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementManager $signalementManager;
    private ?Signalement $signalement = null;
    private ?UserRepository $userRepository = null;
    private MockObject&AddressService $addressServiceMock;
    private ?NotificationRepository $notificationRepository = null;

    protected function setUp(): void
    {
        static::createClient();
        $this->addressServiceMock = $this->createMock(AddressService::class);
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $em;
        $this->signalementManager = static::getContainer()->get(SignalementManager::class);
        $this->signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy([
            'uuid' => '00000000-0000-0000-2025-000000000007',
        ]);
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $this->notificationRepository = static::getContainer()->get(NotificationRepository::class);
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementViewedEvent::NAME, SignalementViewedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementViewed(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-partenaire-multi-ter-34-30@signal-logement.fr']);
        // Empty this data voluntarily to check if the dispatcher handles it.
        $this->signalement
            ->setInseeOccupant(null)
            ->setGeoloc([])
            ->setCpOccupant(null);

        $notifications = $this->notificationRepository->findUnseenNotificationsBy($this->signalement, $user, NotificationType::getForAgent());
        foreach ($notifications as $notification) {
            $this->assertFalse($notification->getIsSeen());
            $this->assertTrue(NotificationType::SUIVI_USAGER !== $notification->getType());
        }

        $signalementViewedEvent = new SignalementViewedEvent($this->signalement, $user);

        $addressResult = json_decode((string) file_get_contents(__DIR__.'/../../files/datagouv/get_api_ban_item_response_13203.json'), true);
        $address = new Address($addressResult);
        $this->addressServiceMock
            ->expects($this->once())
            ->method('getAddress')
            ->willReturn($address);

        $signalementViewedSubscriber = new SignalementViewedSubscriber(
            $this->entityManager,
            $this->addressServiceMock,
            $this->signalementManager,
            $this->notificationRepository,
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementViewedSubscriber);
        $dispatcher->dispatch($signalementViewedEvent, SignalementViewedEvent::NAME);

        /** @var Notification $notification */
        $notifications = $this->notificationRepository->findUnseenNotificationsBy($this->signalement, $user, NotificationType::getForAgent());
        foreach ($notifications as $notification) {
            $this->assertTrue($notification->getIsSeen());
        }

        $this->assertEquals('13203', $this->signalement->getInseeOccupant());
        $this->assertEquals([], $this->signalement->getGeoloc());
        $this->assertEquals('13003', $this->signalement->getCpOccupant());
    }

    public function testOnSuiviViewed(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-partenaire-multi-ter-34-30@signal-logement.fr']);
        $notifications = $this->notificationRepository->findUnseenNotificationsBy($this->signalement, $user, NotificationType::getForUsager());
        foreach ($notifications as $notification) {
            $this->assertFalse($notification->getIsSeen());
            $this->assertTrue(NotificationType::SUIVI_USAGER === $notification->getType());
        }

        $signalementViewedSubscriber = new SignalementViewedSubscriber(
            $this->entityManager,
            $this->addressServiceMock,
            $this->signalementManager,
            $this->notificationRepository,
        );

        $signalementUser = new SignalementUser(
            $this->signalement->getCodeSuivi().':'.UserManager::DECLARANT,
            $this->signalement->getMailDeclarant(),
            $user
        );

        $suiviViewedEvent = new SuiviViewedEvent($this->signalement, $signalementUser);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementViewedSubscriber);
        $dispatcher->dispatch($suiviViewedEvent, SuiviViewedEvent::NAME);

        $user = $signalementUser->getUser();
        $notifications = $this->notificationRepository->findUnseenNotificationsBy($this->signalement, $user, NotificationType::getForUsager());
        foreach ($notifications as $notification) {
            $this->assertTrue($notification->getIsSeen());
        }
    }
}
