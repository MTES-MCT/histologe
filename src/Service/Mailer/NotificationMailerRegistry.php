<?php

namespace App\Service\Mailer;

use App\Service\Mailer\Mail\NotificationMailerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class NotificationMailerRegistry
{
    private iterable $notificationMailers;

    public function __construct(
        #[TaggedIterator('app.notification_mailer')] iterable $notificationMailers
    ) {
        $this->notificationMailers = $notificationMailers;
    }

    public function send(NotificationMail $notification): bool
    {
        /** @var NotificationMailerInterface $notificationMailer */
        foreach ($this->notificationMailers as $notificationMailer) {
            if ($notificationMailer->supports($notification->getType())) {
                return $notificationMailer->send($notification);
            }
        }

        return false;
    }
}
