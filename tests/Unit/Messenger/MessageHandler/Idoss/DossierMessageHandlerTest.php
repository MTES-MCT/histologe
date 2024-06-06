<?php

namespace App\Tests\Unit\Messenger\MessageHandler\Idoss;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Manager\JobEventManager;
use App\Messenger\Message\Idoss\DossierMessage;
use App\Messenger\MessageHandler\Idoss\DossierMessageHandler;
use App\Service\Idoss\IdossService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

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

        $idossServiceMock = $this->createMock(IdossService::class);
        $idossServiceMock->expects($this->once())->method('pushDossier');

        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $jobEventManagerMock->expects($this->once())->method('createJobEvent');

        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock->expects($this->once())->method('serialize')->with($dossierMessage, 'json');

        $dossierMessageHandler = new DossierMessageHandler(
            $idossServiceMock,
            $jobEventManagerMock,
            $serializerMock,
        );

        $dossierMessageHandler($dossierMessage);
    }
}
