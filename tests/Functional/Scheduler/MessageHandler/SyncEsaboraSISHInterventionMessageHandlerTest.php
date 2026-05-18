<?php

declare(strict_types=1);

namespace App\Tests\Functional\Scheduler\MessageHandler;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Scheduler\Message\SyncEsaboraSISHInterventionMessage;
use App\Scheduler\MessageHandler\SyncEsaboraSISHInterventionMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SyncEsaboraSISHInterventionMessageHandlerTest extends KernelTestCase
{
    public function testSendMail(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $signalements = $signalementRepository->findBy([
            'statut' => SignalementStatus::ACTIVE,
            'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
        ]);

        foreach ($signalements as $signalement) {
            $signalement->setProfileDeclarant(ProfileDeclarant::TIERS_PRO);
        }

        $em->flush();

        /** @var SyncEsaboraSISHInterventionMessageHandler $handler */
        $handler = static::getContainer()->get(SyncEsaboraSISHInterventionMessageHandler::class);

        $handler(new SyncEsaboraSISHInterventionMessage());

        $this->assertEmailCount(1);
    }
}
