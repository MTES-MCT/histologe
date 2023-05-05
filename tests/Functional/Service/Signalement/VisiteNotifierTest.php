<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Signalement;
use App\Factory\NotificationFactory;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Signalement\VisiteNotifier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VisiteNotifierTest extends KernelTestCase
{
    private VisiteNotifier $visiteNotifier;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $suiviFactory = static::getContainer()->get(SuiviFactory::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);
        $notificationFactory = static::getContainer()->get(NotificationFactory::class);
        $notificationMailerRegistry = static::getContainer()->get(NotificationMailerRegistry::class);
        $userRepository = static::getContainer()->get(UserRepository::class);

        $this->visiteNotifier = new VisiteNotifier(
            $entityManager,
            $suiviFactory,
            $suiviManager,
            $notificationFactory,
            $notificationMailerRegistry,
            $userRepository,
        );

        $this->signalementRepository = $entityManager->getRepository(Signalement::class);
    }

    public function testNotifyVisiteToConclude()
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $nbNotified = $this->visiteNotifier->notifyVisiteToConclude($signalement);
        $this->assertEquals($nbNotified, 2);
    }
}
