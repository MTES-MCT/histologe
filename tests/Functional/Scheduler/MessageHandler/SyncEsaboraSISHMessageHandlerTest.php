<?php

declare(strict_types=1);

namespace App\Tests\Functional\Scheduler\MessageHandler;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Scheduler\Message\SyncEsaboraSISHMessage;
use App\Scheduler\MessageHandler\SyncEsaboraSISHMessageHandler;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SyncEsaboraSISHMessageHandlerTest extends KernelTestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function testSendMail(): void
    {
        self::bootKernel();

        $filepath = __DIR__.'/../../../../tools/wiremock/src/Resources/Esabora/sish/ws_etat_dossier_sas/etat_importe.json';
        $responseEsabora = json_decode((string) file_get_contents($filepath), true);

        $esaboraService = $this->createMock(EsaboraSISHService::class);

        $esaboraService
            ->method('getStateDossier')
            ->willReturn(new DossierStateSISHResponse(
                $responseEsabora,
                200
            ));

        static::getContainer()->set(EsaboraSISHService::class, $esaboraService);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $signalements = $signalementRepository->findBy([
            'statut' => SignalementStatus::ACTIVE,
            'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
        ]);

        foreach ($signalements as $signalement) {
            $signalement->setProfileDeclarant(ProfileDeclarant::LOCATAIRE);
        }

        $em->flush();

        /** @var SyncEsaboraSISHMessageHandler $handler */
        $handler = static::getContainer()->get(SyncEsaboraSISHMessageHandler::class);

        $handler(new SyncEsaboraSISHMessage());

        $this->assertEmailCount(4);
    }
}
