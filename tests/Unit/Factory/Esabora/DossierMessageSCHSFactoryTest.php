<?php

namespace App\Tests\Unit\Factory\Esabora;

use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Factory\Interconnection\Esabora\DossierMessageSCHSFactory;
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

        $dossierMessageFactory = new DossierMessageSCHSFactory($uploadHandlerServiceMock);
        $dossierMessage = $dossierMessageFactory->createInstance(
            $this->getSignalementAffectation(PartnerType::COMMUNE_SCHS)
        );

        $this->assertCount(2, $dossierMessage->getPiecesJointes());
        $this->assertStringContainsString('document.pdf', $dossierMessage->getPiecesJointesObservation());
        $this->assertStringContainsString('Points signalés', $dossierMessage->getDossierCommentaire());
        $this->assertStringContainsString('Etat grave', $dossierMessage->getDossierCommentaire());
        $this->assertStringContainsString('25', $dossierMessage->getNumeroAdresseSignalement());
        $this->assertStringContainsString('Rue du test', $dossierMessage->getAdresseSignalement());
    }

    /**
     * @dataProvider provideNbChildren
     */
    public function testBuildNbEnfants(string $expectedResult, ?string $nbEnfantsM6 = null, ?string $nbEnfantsP6 = null): void
    {
        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->exactly(0))
            ->method('getTmpFilepath')
            ->willReturn(self::FILE);

        $dossierMessageFactory = new DossierMessageSCHSFactory($uploadHandlerServiceMock);
        $signalement = (new Signalement())->setNbEnfantsM6($nbEnfantsM6)->setNbEnfantsP6($nbEnfantsP6);

        $buildNbEnfantsMethod = new \ReflectionMethod(DossierMessageSCHSFactory::class, 'buildNbEnfants');
        $buildNbEnfantsMethod->setAccessible(true);

        $actualResult = $buildNbEnfantsMethod->invoke($dossierMessageFactory, $signalement);

        $this->assertStringContainsString($expectedResult, $actualResult);
    }

    public function provideNbChildren(): \Generator
    {
        yield 'No child' => ['0 Enfant(s)', null, null];
        yield '1 children M6, 0 children P6' => ['1 Enfant(s)', '1', null];
        yield '3 children M6, 2 children P6' => ['5 Enfant(s)', '3', '2'];
        yield '4+ children M6, 4+ children P6' => ['8+ Enfant(s)', '4+', '4+'];
        yield '4+ children M6, 1 children P6' => ['5+ Enfant(s)', '4+', '1'];
        yield '4+ children M6, 0 children P6' => ['4+ Enfant(s)', '4+', null];
        yield '0 children M6, 4+ children P6' => ['4+ Enfant(s)', null, '4+'];
    }
}
