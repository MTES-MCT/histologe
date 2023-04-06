<?php

namespace App\Service\Mailer\Mail;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.notification_mailer')]
interface NotificationMailerInterface
{
    public function send(NotificationMail $notification): bool;

    public function supports(NotificationMailerType $type): bool;
}
