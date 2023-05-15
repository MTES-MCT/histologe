<?php

namespace App\Tests\Unit\Factory\Esabora;

use App\Entity\Enum\PartnerType;
use App\Factory\Esabora\DossierMessageSCHSFactory;
use App\Service\Esabora\AddressParser;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class DossierMessageSCHSFactoryTest extends TestCase
{
    use FixturesHelper;

    private const FILE = __DIR__.'/../../../../src/DataFixtures/Images/sample.png';

    public function testDossierMessageFactoryIsFullyCreated(): void
    {
        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->exactly(2))
            ->method('getTmpFilepath')
            ->willReturn(self::FILE);

        $dossierMessageFactory = new DossierMessageSCHSFactory(new AddressParser(), $uploadHandlerServiceMock);
        $dossierMessage = $dossierMessageFactory->createInstance(
            $this->getSignalementAffectation(PartnerType::COMMUNE_SCHS)
        );

        $this->assertCount(2, $dossierMessage->getPiecesJointes());
        $this->assertStringContainsString('Doc', $dossierMessage->getPiecesJointesObservation());
        $this->assertStringContainsString('Points signalés', $dossierMessage->getDossierCommentaire());
        $this->assertStringContainsString('Etat grave', $dossierMessage->getDossierCommentaire());
        $this->assertStringContainsString('25', $dossierMessage->getNumeroAdresseSignalement());
        $this->assertStringContainsString('Rue du test', $dossierMessage->getAdresseSignalement());
    }
}
