<?php

namespace App\Tests\Unit\Factory\Esabora;

use App\Entity\Enum\PartnerType;
use App\Factory\Esabora\DossierMessageSISHFactory;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\AddressParser;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class DossierMessageSISHFactoryTest extends TestCase
{
    use FixturesHelper;
    private const FILE = __DIR__.'/../../../../src/DataFixtures/Images/sample.png';

    public function testDossierMessageFactoryIsFullyCreated()
    {
        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->exactly(2))
            ->method('getTmpFilepath')
            ->willReturn(self::FILE);

        $dossierMessageFactory = new DossierMessageSISHFactory(new AddressParser(), $uploadHandlerServiceMock);
        $dossierMessage = $dossierMessageFactory->createInstance(
            $this->getSignalementAffectation(PartnerType::ARS)
        );

        $this->assertCount(2, $dossierMessage->getPiecesJointesDocuments());
        $this->assertEquals(PartnerType::ARS->value, $dossierMessage->getPartnerType());
        $this->assertStringContainsString('Etat', $dossierMessage->getSignalementProblemes());
        $this->assertCount(1, $dossierMessage->getPersonnes());
        $this->assertEquals('Rue du test', $dossierMessage->getLocalisationAdresse1());
        $this->assertEquals('25', $dossierMessage->getLocalisationNumero());
        $this->assertEquals(5, \strlen($dossierMessage->getLocalisationCodePostal()));
        $this->assertNotNull($dossierMessage->getLocalisationVille());
        $this->assertEquals('H', $dossierMessage->getSasLogicielProvenance());
        $this->assertEquals(
            AbstractEsaboraService::SIGNALEMENT_ORIGINE,
            $dossierMessage->getSignalementOrigine()
        );
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            \DateTimeImmutable::createFromFormat('d/m/Y H:i', $dossierMessage->getSasDateAffectation())
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            \DateTimeImmutable::createFromFormat('d/m/Y', $dossierMessage->getSignalementDate())
        );
    }
}
