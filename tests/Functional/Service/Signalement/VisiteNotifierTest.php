<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Factory\NotificationFactory;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\NotificationAndMailSender;
use App\Service\Signalement\VisiteNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VisiteNotifierTest extends KernelTestCase
{
    private VisiteNotifier $visiteNotifier;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $notificationFactory = static::getContainer()->get(NotificationFactory::class);
        $notificationMailerRegistry = static::getContainer()->get(NotificationMailerRegistry::class);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $notificationAndMailerSender = static::getContainer()->get(NotificationAndMailSender::class);
        $userSignalementSubscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);

        $this->visiteNotifier = new VisiteNotifier(
            $entityManager,
            $notificationFactory,
            $notificationMailerRegistry,
            $userRepository,
            $notificationAndMailerSender,
            $userSignalementSubscriptionRepository,
        );

        $this->signalementRepository = $entityManager->getRepository(Signalement::class);
    }

    public function testNotifyVisiteToConclude(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
        /** @var Intervention $intervention * */
        $intervention = $signalement->getInterventions()[0];

        $nbNotified = $this->visiteNotifier->notifyVisiteToConclude($intervention);
        $this->assertEquals(3, $nbNotified);
    }

    public function testNotifyVisiteToConclude69(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000003']);
        /** @var Intervention $intervention * */
        $intervention = $signalement->getInterventions()[0];

        $nbNotified = $this->visiteNotifier->notifyVisiteToConclude($intervention);
        $this->assertEquals(1, $nbNotified);
    }
}
