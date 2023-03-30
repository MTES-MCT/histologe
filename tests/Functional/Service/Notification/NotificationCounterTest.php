<?php

namespace App\Tests\Functional\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\Notification\NotificationCounter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationCounterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testCountUnseenNotification(): void
    {
        $notificationRepository = $this->entityManager->getRepository(Notification::class);
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $notificationCount = (new NotificationCounter($notificationRepository))->countUnseenNotification($user);
        $this->assertEquals(4, $notificationCount);
    }
}
