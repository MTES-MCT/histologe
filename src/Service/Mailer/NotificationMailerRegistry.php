<?php

namespace App\Service\Mailer;

use App\Service\Mailer\Mail\NotificationMailerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class NotificationMailerRegistry
{
    /**
     * @var iterable<string, NotificationMailerInterface>
     */
    private iterable $notificationMailers;

    /**
     * @param iterable<string, NotificationMailerInterface> $notificationMailers
     */
    public function __construct(
        #[AutowireIterator('app.notification_mailer')] iterable $notificationMailers,
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
