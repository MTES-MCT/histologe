<?php

declare(strict_types=1);

namespace App\Tests\Functional\Scheduler\MessageHandler;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Scheduler\Message\SyncEsaboraSCHSMessage;
use App\Scheduler\MessageHandler\SyncEsaboraSCHSMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SyncEsaboraSCHSMessageHandlerTest extends KernelTestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function testSendMails(): void
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

        /** @var SyncEsaboraSCHSMessageHandler $handler */
        $handler = static::getContainer()->get(SyncEsaboraSCHSMessageHandler::class);

        $handler(new SyncEsaboraSCHSMessage());

        $this->assertEmailCount(2);
    }
}
