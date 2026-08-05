<?php

namespace App\EventSubscriber;

use App\Entity\Enum\NotificationType;
use App\Entity\Signalement;
use App\Event\SignalementViewedEvent;
use App\Event\SuiviViewedEvent;
use App\Repository\NotificationRepository;
use App\Security\User\SignalementUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SignalementViewedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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

    /**
     * @param array<NotificationType> $includedNotificationTypes
     */
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
}
