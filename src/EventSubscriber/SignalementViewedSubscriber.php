<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Event\SignalementViewedEvent;
use App\Manager\SignalementManager;
use App\Service\Gouv\Ban\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementViewedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AddressService $addressService,
        private SignalementManager $signalementManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementViewedEvent::NAME => 'onSignalementViewed',
        ];
    }

    public function onSignalementViewed(SignalementViewedEvent $event)
    {
        $signalement = $event->getSignalement();
        $user = $event->getUser();

        $notifications = $this->entityManager->getRepository(Notification::class)->findBy([
            'signalement' => $signalement,
            'user' => $user,
            'isSeen' => false,
        ]);

        /** @var Notification $notification */
        foreach ($notifications as $notification) {
            $notification->setIsSeen(true);
            $this->entityManager->persist($notification);
        }

        $this->updateGeolocationDataIfNeeded($signalement);

        $this->entityManager->flush();
    }

    private function updateGeolocationDataIfNeeded(Signalement $signalement): void
    {
        if (empty($signalement->getInseeOccupant())) {
            $address = $this->addressService->getAddress($signalement->getAddressCompleteOccupant());
            $this->signalementManager->updateAddressOccupantFromAddress($signalement, $address);
            $this->signalementManager->persist($signalement);
        }
    }
}
