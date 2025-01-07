<?php

namespace App\Tests\Unit\Factory\Esabora;

use App\Entity\Enum\PartnerType;
use App\Factory\Interconnection\Esabora\DossierMessageSISHFactory;
use App\Repository\SuiviRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Doctrine\ORM\NonUniqueResultException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class DossierMessageSISHFactoryTest extends TestCase
{
    use FixturesHelper;
    private const string FILE = __DIR__.'/../../../../src/DataFixtures/Images/sample.png';

    /**
     * @throws NonUniqueResultException
     */
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

        $dossierMessageFactory = new DossierMessageSISHFactory(
            $suiviRepositoryMock,
            $uploadHandlerServiceMock,
            $parameterBagMock,
            $urlGeneratorMock,
        );

        $signalement->setNumAppartOccupant('à gauche de l\'entrée principale, appart 11');
        $signalement->getFiles()->first()->setTitle('un titre de fichier très long pour tester le tronquage à 100 caractères ce qui devrait donc être là le reste n\'apparait pas');
        $signalement->setInseeOccupant(null);

        $dossierMessage = $dossierMessageFactory->createInstance($affectation);
        $this->assertEquals(1.5, $dossierMessage->getSignalementScore());
        $this->assertCount(2, $dossierMessage->getPiecesJointesDocuments());
        $this->assertEquals(PartnerType::ARS, $dossierMessage->getPartnerType());
        $this->assertStringContainsString('Etat', $dossierMessage->getSignalementProblemes());
        $this->assertCount(2, $dossierMessage->getPersonnes());
        $this->assertEquals('Rue du test', $dossierMessage->getLocalisationAdresse1());
        $this->assertEquals('25', $dossierMessage->getLocalisationNumero());
        $this->assertEquals(5, \strlen($dossierMessage->getLocalisationCodePostal()));
        $this->assertNotNull($dossierMessage->getLocalisationVille());
        $this->assertEquals('H', $dossierMessage->getSasLogicielProvenance());
        $this->assertEquals(75, $dossierMessage->getSitLogementSuperficie());
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

    public function testSupportsThrowsLogicExceptionForInvalidUrlOnLocalhost()
    {
        $affectation = $this->getAffectation(PartnerType::ARS);

        $context = new RequestContext('localhost'); // On simule que l'hôte est localhost
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('getContext')->willReturn($context);

        $dossierMessageSISHFactory = new DossierMessageSISHFactory(
            $this->createMock(SuiviRepository::class),
            $this->createMock(UploadHandlerService::class),
            $this->createMock(ParameterBagInterface::class),
            $urlGenerator
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Partner url must contain "histologe_wiremock" when on localhost.');

        $dossierMessageSISHFactory->supports($affectation);
    }
}
