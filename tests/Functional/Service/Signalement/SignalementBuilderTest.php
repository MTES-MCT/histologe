<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Factory\FileFactory;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Repository\DesordreCategorieRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\TerritoryRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\Signalement\SignalementBuilder;
use App\Service\Signalement\SignalementInputValueMapper;
use App\Service\Signalement\ZipcodeProvider;
use App\Service\Token\TokenGeneratorInterface;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class SignalementBuilderTest extends KernelTestCase
{
    use FixturesHelper;

    private const FR_PHONE_COUNTRY_CODE = '33';

    protected SignalementBuilder $signalementBuilder;

    protected function setUp(): void
    {
        self::bootKernel();

        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $zipcodeProvider = static::getContainer()->get(ZipcodeProvider::class);
        $referenceGenerator = static::getContainer()->get(ReferenceGenerator::class);
        $tokenGenerator = static::getContainer()->get(TokenGeneratorInterface::class);
        $signalementDraftRequestSerializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        $typeCompositionLogementFactory = static::getContainer()->get(TypeCompositionLogementFactory::class);
        $situationFoyerFactory = static::getContainer()->get(SituationFoyerFactory::class);
        $informationProcedureFactory = static::getContainer()->get(InformationProcedureFactory::class);
        $informationComplementaireFactory = static::getContainer()->get(InformationComplementaireFactory::class);
        $fileFactory = static::getContainer()->get(FileFactory::class);
        $uploadHandlerService = static::getContainer()->get(UploadHandlerService::class);
        $security = static::getContainer()->get(Security::class);
        $signalementInputValueMapper = static::getContainer()->get(SignalementInputValueMapper::class);
        $desordreCategorieRepository = static::getContainer()->get(DesordreCategorieRepository::class);
        $desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
        $desordrePrecisionRepository = static::getContainer()->get(DesordrePrecisionRepository::class);
        $desordreTraitementProcessor = static::getContainer()->get(DesordreTraitementProcessor::class);

        $this->signalementBuilder = new SignalementBuilder(
            $territoryRepository,
            $zipcodeProvider,
            $referenceGenerator,
            $tokenGenerator,
            $signalementDraftRequestSerializer,
            $typeCompositionLogementFactory,
            $situationFoyerFactory,
            $informationProcedureFactory,
            $informationComplementaireFactory,
            $fileFactory,
            $uploadHandlerService,
            $security,
            $signalementInputValueMapper,
            $desordreCategorieRepository,
            $desordreCritereRepository,
            $desordrePrecisionRepository,
            $desordreTraitementProcessor
        );
    }

    public function testBuildSignalement(): void
    {
        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        $signalementDraft = (new SignalementDraft())
            ->setPayload($payload)
            ->setProfileDeclarant(ProfileDeclarant::LOCATAIRE)
            ->setStatus(SignalementDraftStatus::EN_COURS)
            ->setCurrentStep('informations_complementaires')
            ->setEmailDeclarant($payload['vos_coordonnees_occupant_email']);

        $signalement = $this->signalementBuilder
            ->createSignalementBuilderFrom($signalementDraft)
            ->withAdressesCoordonnees()
            ->withTypeCompositionLogement()
            ->withSituationFoyer()
            ->withProcedure()
            ->withInformationComplementaire()
            ->withFiles()
            ->build();

        $this->assertCount(7, $signalement->getFiles());
        $this->assertNotEmpty($signalement->getUuid());
        $this->assertNotEmpty($signalement->getReference());
        $this->assertNotEmpty($signalement->getCodeSuivi());
        $this->assertEquals('13', $signalement->getTerritory()->getZip());
        $this->assertEquals(ProfileDeclarant::LOCATAIRE, $signalement->getProfileDeclarant());
        $this->assertEquals(
            '+'.self::FR_PHONE_COUNTRY_CODE.'0644784515',
            $signalement->getTelOccupant()
        );
        $this->assertEquals(
            '+269'.'3621161',
            $signalement->getTelOccupantBis()
        );
        $this->assertEquals(
            '+'.self::FR_PHONE_COUNTRY_CODE.'0644784515',
            $signalement->getTelDeclarant()
        );
        $this->assertEquals(
            '+269'.'3621161',
            $signalement->getTelDeclarantSecondaire()
        );
        $this->assertEquals(
            '+'.self::FR_PHONE_COUNTRY_CODE.'0644784516',
            $signalement->getTelProprio()
        );
        $this->assertEquals(
            '+'.self::FR_PHONE_COUNTRY_CODE.'0644784517',
            $signalement->getTelProprioSecondaire()
        );

        $this->assertEquals('mme', $signalement->getCiviliteOccupant());
        $this->assertEquals('Locataire Nom', $signalement->getNomOccupant());
        $this->assertEquals('Locataire Prenom', $signalement->getPrenomOccupant());
        $this->assertEquals('locataire-01@histologe.fr', $signalement->getMailOccupant());
        $this->assertEquals('appartement', $signalement->getNatureLogement());
        $this->assertEquals('33 Rue des phoceens', $signalement->getAdresseOccupant());
        $this->assertEquals('13002', $signalement->getCpOccupant());
        $this->assertEquals('Marseille', $signalement->getVilleOccupant());
        $this->assertEquals('13202', $signalement->getInseeOccupant());
        $this->assertEquals('5', $signalement->getEtageOccupant());
        $this->assertEquals('A', $signalement->getEscalierOccupant());
        $this->assertArrayHasKey('lat', $signalement->getGeoloc());
        $this->assertArrayHasKey('lng', $signalement->getGeoloc());

        $this->assertTrue($signalement->getIsLogementSocial());
        $this->assertTrue($signalement->getIsBailEnCours());
        $this->assertTrue($signalement->getIsProprioAverti());
        $this->assertFalse($signalement->getIsFondSolidariteLogement());
        $this->assertFalse($signalement->getIsRsa());
        $this->assertTrue($signalement->getIsCguAccepted());
        $this->assertTrue(\in_array($signalement->getIsAllocataire(), ['CAF', 'MSA', '0']));
        $this->assertTrue($signalement->getIsRelogement());
        $this->assertFalse($signalement->getIsConstructionAvant1949());
        $this->assertFalse($signalement->getIsNotOccupant());
        $this->assertEquals(300, $signalement->getMontantAllocation());
        $this->assertEquals(500, $signalement->getLoyer());
        $this->assertEquals(2, $signalement->getNbPiecesLogement());
        $this->assertEquals(3, $signalement->getNbOccupantsLogement());
        $this->assertEquals(45, $signalement->getSuperficie());
        $this->assertEquals(5, $signalement->getNbNiveauxLogement());
        $this->assertFalse($signalement->getIsConstructionAvant1949());

        $this->assertEquals(new \DateTimeImmutable('1970-10-01'), $signalement->getDateNaissanceOccupant());
        $this->assertEquals(new \DateTimeImmutable('2020-10-01'), $signalement->getDateEntree());

        $typeCompositionLogement = array_filter($signalement->getTypeCompositionLogement()->toArray());
        $this->assertEquals($this->getLocataireTypeComposition(transformPiecesAVivre: true), $typeCompositionLogement);

        $situationFoyer = array_filter($signalement->getSituationFoyer()->toArray());
        $this->assertEquals($this->getLocataireSituationFoyer(), $situationFoyer);

        $informationProcedure = array_filter($signalement->getInformationProcedure()->toArray());
        $this->assertEquals($this->getLocataireInformationProcedure(), $informationProcedure);

        $informationComplementaire = array_filter($signalement->getInformationComplementaire()->toArray());
        $this->assertEquals($this->getLocataireInformationComplementaire(), $informationComplementaire);
    }

    public function testIsConstructionAvant1949(): void
    {
        $this->assertNull($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', [null]));
        $this->assertTrue($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', ['1867']));
        $this->assertFalse($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', ['1949']));
    }

    private function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(\get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
