<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Tests\UserHelper;
use PHPUnit\Framework\TestCase;

class NotificationFactoryTest extends TestCase
{
    use UserHelper;

    public function testCreateInstanceNotification(): void
    {
        $user = $this->getUserFromRole(User::ROLE_USER_PARTNER);
        $suivi = (new Suivi())
            ->setType(Suivi::TYPE_PARTNER)
            ->setCreatedBy($user)
            ->setDescription('Hello world')
            ->setSignalement(new Signalement());

        $notification = (new NotificationFactory())->createInstanceFrom($user, $suivi);

        $this->assertEquals(Notification::TYPE_SUIVI, $notification->getType());
        $this->assertEquals('Hello world', $notification->getSuivi()->getDescription());
    }
}
