<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Event\SignalementViewedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalementViewedSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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

        $this->entityManager->flush();
    }
}
