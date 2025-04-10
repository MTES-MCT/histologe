<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Affectation;
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
        $this->notificationFactory = $this->notificationFactory = new NotificationFactory(
            featureEmailRecap: $kernel->getContainer()->getParameter('feature_email_recap')
        );
    }

    public function testCreateInstanceNotificationNouveauSuivi(): void
    {
        $user = $this->getUserFromRole(User::ROLE_USER_PARTNER);
        $user->setIsMailingActive(true);
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
        $this->assertEquals($user, $notification->getUser());
        $this->assertEquals($suivi, $notification->getSuivi());
        $this->assertEquals($suivi->getSignalement(), $notification->getSignalement());
        $this->assertTrue($notification->isWaitMailingSummary());
    }

    public function testCreateInstanceNotificationNouveauSignalement(): void
    {
        $user = $this->getUserFromRole(User::ROLE_ADMIN_TERRITORY);
        $user->setIsMailingActive(true);
        $user->setIsMailingSummary(false);
        $signalement = new Signalement();

        $notification = $this->notificationFactory->createInstanceFrom(
            user: $user,
            type: NotificationType::NOUVEAU_SIGNALEMENT,
            signalement: $signalement
        );

        $this->assertEquals(NotificationType::NOUVEAU_SIGNALEMENT, $notification->getType());
        $this->assertEquals($user, $notification->getUser());
        $this->assertEquals($signalement, $notification->getSignalement());
        $this->assertFalse($notification->isWaitMailingSummary());
    }

    public function testCreateInstanceNotificationNouvelleAffectation(): void
    {
        $user = $this->getUserFromRole(User::ROLE_USER_PARTNER);
        $signalement = new Signalement();
        $affectation = new Affectation();
        $affectation->setSignalement($signalement);

        $notification = $this->notificationFactory->createInstanceFrom(
            user: $user,
            type: NotificationType::NOUVELLE_AFFECTATION,
            affectation: $affectation
        );
        $this->assertEquals(NotificationType::NOUVELLE_AFFECTATION, $notification->getType());
        $this->assertEquals($user, $notification->getUser());
        $this->assertEquals($affectation, $notification->getAffectation());
        $this->assertEquals($signalement, $notification->getSignalement());
        $this->assertFalse($notification->isWaitMailingSummary());
    }
}
