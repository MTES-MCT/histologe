<?php

namespace App\Tests\Functional\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NotificationCounter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationCounterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    public function testCountUnseenNotification(): void
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->entityManager->getRepository(Notification::class);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $notificationCount = (new NotificationCounter($notificationRepository))->countUnseenNotification($user);
        $this->assertEquals(7, $notificationCount);
    }
}
