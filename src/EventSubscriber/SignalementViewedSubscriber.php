<?php

namespace App\EventSubscriber;

use App\Entity\Enum\NotificationType;
use App\Entity\Signalement;
use App\Event\SignalementViewedEvent;
use App\Event\SuiviViewedEvent;
use App\Manager\SignalementManager;
use App\Repository\NotificationRepository;
use App\Security\User\SignalementUser;
use App\Service\Gouv\Ban\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementViewedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressService $addressService,
        private readonly SignalementManager $signalementManager,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SignalementViewedEvent::NAME => 'onSignalementViewed',
            SuiviViewedEvent::NAME => 'onSuiviViewed',
        ];
    }

    public function onSignalementViewed(SignalementViewedEvent $event): void
    {
        $signalement = $event->getSignalement();
        $user = $event->getUser();
        $this->markNotificationsAsSeen(
            signalement: $signalement,
            user: $user,
            includedNotificationTypes: NotificationType::getForAgent()
        );
        $this->updateGeolocationDataIfNeeded($signalement);

        $this->entityManager->flush();
    }

    public function onSuiviViewed(SuiviViewedEvent $event): void
    {
        $signalement = $event->getSignalement();
        $user = $event->getUser();
        $this->markNotificationsAsSeen(
            signalement: $signalement,
            user: $user,
            includedNotificationTypes: NotificationType::getForUsager()
        );
        $this->entityManager->flush();
    }

    private function markNotificationsAsSeen(
        Signalement $signalement,
        UserInterface $user,
        array $includedNotificationTypes = [],
    ): void {
        if ($user instanceof SignalementUser) {
            $user = $user->getUser();
        }
        if (!$user) {
            return;
        }
        $notifications = $this->notificationRepository->findUnseenNotificationsBy(
            signalement: $signalement,
            user: $user,
            includedNotificationTypes: $includedNotificationTypes
        );

        foreach ($notifications as $notification) {
            $notification->setIsSeen(true);
            $notification->setSeenAt(new \DateTimeImmutable());
            $this->entityManager->persist($notification);
        }
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
