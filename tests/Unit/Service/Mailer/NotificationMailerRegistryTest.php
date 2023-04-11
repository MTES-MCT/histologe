<?php

namespace App\Tests\Service\Mailer;

use App\Service\Mailer\Mail\NotificationMailerInterface;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PHPUnit\Framework\TestCase;

class NotificationMailerRegistryTest extends TestCase
{
    public function testSend(): void
    {
        $notification = new NotificationMail(
            type: NotificationMailerType::TYPE_CRON,
            to: 'john.doe@yopmail.com',
            message: 'Hi',
            cronLabel: 'Cron run successfully',
            cronCount: 5
        );

        $notificationMailer1 = $this->createMock(NotificationMailerInterface::class);
        $notificationMailer1->expects($this->once())
            ->method('supports')
            ->with(NotificationMailerType::TYPE_CRON)
            ->willReturn(false);
        $notificationMailer1->expects($this->never())
            ->method('send');

        $notificationMailer2 = $this->createMock(NotificationMailerInterface::class);
        $notificationMailer2->expects($this->once())
            ->method('supports')
            ->with(NotificationMailerType::TYPE_CRON)
            ->willReturn(true);
        $notificationMailer2->expects($this->once())
            ->method('send')
            ->with($notification)
            ->willReturn(true);

        $notificationMailers = new \ArrayIterator([$notificationMailer1, $notificationMailer2]);
        $notificationMailerRegistry = new NotificationMailerRegistry($notificationMailers);

        $this->assertTrue($notificationMailerRegistry->send($notification));
    }
}
