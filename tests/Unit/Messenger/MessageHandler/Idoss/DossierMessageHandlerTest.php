<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Idoss;

use App\Entity\Affectation;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Messenger\MessageHandler\Idoss\DossierMessageHandler;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Service\Idoss\IdossService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossierMessageHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testProcessDossierMessage(): void
    {
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partner = $partnerRepository->findOneBy(['email' => 'partenaire-13-05@histologe.fr']);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectation = $affectationRepository->findOneBy(['partner' => $partner]);
        $dossierMessage = new DossierMessage($affectation);

        $jobEventMock = $this->createMock(JobEvent::class);
        $jobEventMock->expects($this->once())->method('getStatus')->willReturn(JobEvent::STATUS_SUCCESS);

        $idossServiceMock = $this->createMock(IdossService::class);
        $idossServiceMock->method('pushDossier')->willReturn($jobEventMock);
        $idossServiceMock->expects($this->once())->method('pushDossier');
        $idossServiceMock->expects($this->once())->method('uploadFiles');

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);

        $dossierMessageHandler = new DossierMessageHandler(
            $idossServiceMock,
            $signalementRepository,
            $partnerRepository
        );

        $dossierMessageHandler($dossierMessage);
    }
}
