<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\NotificationType;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationFactoryTest extends KernelTestCase
{
    use UserHelper;
    private NotificationFactory $notificationFactory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->notificationFactory = $kernel->getContainer()->get(NotificationFactory::class);
    }

    public function testCreateInstanceNotification(): void
    {
        $user = $this->getUserFromRole(User::ROLE_USER_PARTNER);
        $suivi = (new Suivi())
            ->setType(Suivi::TYPE_PARTNER)
            ->setCreatedBy($user)
            ->setDescription('Hello world')
            ->setSignalement(new Signalement());

        $notification = $this->notificationFactory->createInstanceFrom(
            user: $user,
            type: NotificationType::NOUVEAU_SUIVI,
            suivi: $suivi
        );

        $this->assertEquals(NotificationType::NOUVEAU_SUIVI, $notification->getType());
        $this->assertEquals('Hello world', $notification->getSuivi()->getDescription());
    }
}
