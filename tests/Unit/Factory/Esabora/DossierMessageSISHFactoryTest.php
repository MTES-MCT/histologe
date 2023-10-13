<?php

namespace App\Tests\Unit\Factory\Esabora;

use App\Entity\Enum\PartnerType;
use App\Factory\Esabora\DossierMessageSISHFactory;
use App\Repository\SuiviRepository;
use App\Service\DataGouv\AddressService;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DossierMessageSISHFactoryTest extends TestCase
{
    use FixturesHelper;
    private const FILE = __DIR__.'/../../../../src/DataFixtures/Images/sample.png';

    public function testDossierMessageFactoryIsFullyCreated()
    {
        $affectation = $this->getSignalementAffectation(PartnerType::ARS);
        $signalement = $affectation->getSignalement();

        $suiviRepositoryMock = $this->createMock(SuiviRepository::class);
        $suiviRepositoryMock
            ->expects($this->once())
            ->method('findFirstSuiviBy')
            ->willReturn($this->getSuiviPartner());

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->exactly(2))
            ->method('getTmpFilepath')
            ->willReturn(self::FILE);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock
            ->expects($this->once())
            ->method('get')
            ->with('host_url')
            ->willReturn('https://localhost');

        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $urlGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with('back_signalement_view')
            ->willReturn('/bo/signalements/00000000-0000-0000-2022-000000000001');

        $addressService = $this->createMock(AddressService::class);
        $addressService
            ->expects($this->once())
            ->method('getCodeInsee')
            ->willReturn($signalement->getInseeOccupant());

        $dossierMessageFactory = new DossierMessageSISHFactory(
            $suiviRepositoryMock,
            $uploadHandlerServiceMock,
            $parameterBagMock,
            $urlGeneratorMock,
            $addressService
        );

        $signalement->setNumAppartOccupant('à gauche de l\'entrée principale, appart 11');
        $signalement->getFiles()->first()->setTitle('un titre de fichier très long pour tester le tronquage à 100 caractères ce qui devrait donc être là le reste n\'apparait pas');
        $signalement->setInseeOccupant(null);

        $dossierMessage = $dossierMessageFactory->createInstance($affectation);

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
