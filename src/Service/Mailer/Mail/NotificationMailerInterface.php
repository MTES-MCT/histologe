<?php

namespace App\Service\Mailer\Mail;

use App\Service\Mailer\Notification;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.notification_mailer')]
interface NotificationMailerInterface
{
    public function send(Notification $notification): bool;

    public function supports(NotificationMailerType $type): bool;
}
