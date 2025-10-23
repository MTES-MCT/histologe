<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\SignalementDraft;
use App\Factory\Signalement\InformationComplementaireFactory;
use App\Factory\Signalement\InformationProcedureFactory;
use App\Factory\Signalement\SituationFoyerFactory;
use App\Factory\Signalement\TypeCompositionLogementFactory;
use App\Manager\DesordreCritereManager;
use App\Repository\BailleurRepository;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\DesordreTraitement\DesordreTraitementProcessor;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\Signalement\SignalementBuilder;
use App\Service\Signalement\ZipcodeProvider;
use App\Tests\FixturesHelper;
use App\Tests\KernelServiceHelperTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementBuilderTest extends KernelTestCase
{
    use FixturesHelper;
    use KernelServiceHelperTrait;

    private const FR_PHONE_COUNTRY_CODE = '33';
    private ?EntityManagerInterface $entityManager = null;

    protected SignalementBuilder $signalementBuilder;

    private DesordreCritereRepository $desordreCritereRepository;

    private DesordrePrecisionRepository $desordrePrecisionRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = $this->getService(EntityManagerInterface::class);
        $this->desordreCritereRepository = $this->getService(DesordreCritereRepository::class);
        $this->desordrePrecisionRepository = $this->getService(DesordrePrecisionRepository::class);
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        /** @var ReferenceGenerator $referenceGenerator */
        $referenceGenerator = static::getContainer()->get(ReferenceGenerator::class);
        /** @var SignalementDraftRequestSerializer $signalementDraftRequestSerializer */
        $signalementDraftRequestSerializer = static::getContainer()->get(SignalementDraftRequestSerializer::class);
        /** @var TypeCompositionLogementFactory $typeCompositionLogementFactory */
        $typeCompositionLogementFactory = static::getContainer()->get(TypeCompositionLogementFactory::class);
        /** @var SituationFoyerFactory $situationFoyerFactory */
        $situationFoyerFactory = static::getContainer()->get(SituationFoyerFactory::class);
        /** @var InformationProcedureFactory $informationProcedureFactory */
        $informationProcedureFactory = static::getContainer()->get(InformationProcedureFactory::class);
        /** @var InformationComplementaireFactory $informationComplementaireFactory */
        $informationComplementaireFactory = static::getContainer()->get(InformationComplementaireFactory::class);
        /** @var DesordreTraitementProcessor $desordreTraitementProcessor */
        $desordreTraitementProcessor = static::getContainer()->get(DesordreTraitementProcessor::class);
        /** @var DesordreCritereManager $desordreCritereManager */
        $desordreCritereManager = static::getContainer()->get(DesordreCritereManager::class);
        /** @var CriticiteCalculator $criticiteCalculator */
        $criticiteCalculator = static::getContainer()->get(CriticiteCalculator::class);
        /** @var SignalementQualificationUpdater $signalementQualificationUpdater */
        $signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        /** @var DesordreCompositionLogementLoader $desordreCompositionLogementLoader */
        $desordreCompositionLogementLoader = static::getContainer()->get(DesordreCompositionLogementLoader::class);
        /** @var ZipcodeProvider $zipcodeProvider */
        $zipcodeProvider = static::getContainer()->get(ZipcodeProvider::class);

        $this->signalementBuilder = new SignalementBuilder(
            $bailleurRepository,
            $referenceGenerator,
            $signalementDraftRequestSerializer,
            $typeCompositionLogementFactory,
            $situationFoyerFactory,
            $informationProcedureFactory,
            $informationComplementaireFactory,
            $this->desordreCritereRepository,
            $this->desordrePrecisionRepository,
            $desordreTraitementProcessor,
            $desordreCritereManager,
            $criticiteCalculator,
            $signalementQualificationUpdater,
            $desordreCompositionLogementLoader,
            $zipcodeProvider,
            true,
            '["30","34","13"]',
        );
    }

    public function testBuildSignalement(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            (string) file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
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
            ->withDesordres()
            ->build()
        ;

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
            '+2693621161',
            $signalement->getTelOccupantBis()
        );
        $this->assertNull($signalement->getTelDeclarant());
        $this->assertNull($signalement->getMailDeclarant());
        $this->assertNull($signalement->getTelDeclarantSecondaire());
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
        $this->assertEquals('locataire-01@signal-logement.fr', $signalement->getMailOccupant());
        $this->assertEquals('appartement', $signalement->getNatureLogement());
        $this->assertEquals('33 Rue des phoceens', $signalement->getAdresseOccupant());
        $this->assertEquals('13002', $signalement->getCpOccupant());
        $this->assertEquals('Marseille', $signalement->getVilleOccupant());
        $this->assertEquals('5', $signalement->getEtageOccupant());
        $this->assertEquals('A', $signalement->getEscalierOccupant());

        $this->assertEquals('13 HABITAT', $signalement->getNomProprio());
        $this->assertEquals('Sandrine', $signalement->getPrenomProprio());
        $this->assertEquals('sandrine@signal-logement.fr', $signalement->getMailProprio());
        $this->assertEquals('10 rue du 14 juillet', $signalement->getAdresseProprio());
        $this->assertEquals('64000', $signalement->getCodePostalProprio());
        $this->assertEquals('Pau', $signalement->getVilleProprio());

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

        $this->assertCount(5, $signalement->getDesordreCriteres());
        $this->assertCount(5, $signalement->getDesordrePrecisions());

        $this->entityManager->commit();
    }

    public function testBuildSignalementAllocataire(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            (string) file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );
        $payload['logement_social_allocation'] = 'non';
        $payload['logement_social_montant_allocation'] = null;
        unset($payload['logement_social_allocation_caisse']);

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
            ->withDesordres()
            ->build()
        ;
        $this->assertEquals('0', $signalement->getIsAllocataire());

        $this->entityManager->commit();
    }

    public function testBuildSignalementInjonctionBailleur(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            (string) file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );
        $payload['signalement_concerne_logement_social_autre_tiers'] = 'non';
        $payload['injonction_bailleur_choice'] = 'oui';
        unset($payload['desordres_logement_electricite_installation_dangereuse']);
        unset($payload['desordres_logement_electricite_manque_prises_details_multiprises']);

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
            ->withDesordres()
            ->withStatus()
            ->build()
        ;
        $this->assertEquals(SignalementStatus::INJONCTION_BAILLEUR, $signalement->getStatut());

        $this->entityManager->commit();
    }

    public function testBuildSignalementNoInjonctionBailleurBecauseDanger(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );
        $payload['signalement_concerne_logement_social_autre_tiers'] = 'non';
        $payload['injonction_bailleur_choice'] = 'oui';

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
            ->withDesordres()
            ->withStatus()
            ->build()
        ;
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());

        $this->entityManager->commit();
    }

    public function testBuildSignalementNoInjonctionBailleurBecauseInsalubrite(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );
        $payload['signalement_concerne_logement_social_autre_tiers'] = 'non';
        $payload['injonction_bailleur_choice'] = 'oui';
        unset($payload['desordres_logement_electricite_installation_dangereuse']);
        unset($payload['desordres_logement_electricite_manque_prises_details_multiprises']);
        $payload['type_logement_commodites_wc_cuisine'] = 'oui';

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
            ->withDesordres()
            ->withStatus()
            ->build()
        ;
        $this->assertEquals(SignalementStatus::NEED_VALIDATION, $signalement->getStatut());

        $this->entityManager->commit();
    }

    public function testBuildSignalementAllDesordres(): void
    {
        $this->entityManager->beginTransaction();

        $payload = json_decode(
            (string) file_get_contents(__DIR__.'../../../../../src/DataFixtures/Files/signalement_draft_payload/locataire_all_in.json'),
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
            ->withDesordres()
            ->build();

        $this->assertCount(56, $signalement->getDesordreCriteres());
        $this->assertCount(64, $signalement->getDesordrePrecisions());

        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => 'desordres_type_composition_logement_sous_combles']
        );
        $this->assertTrue($signalement->getDesordreCriteres()->contains($desordreCritere));

        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => 'desordres_type_composition_logement_cuisine']
        );
        $this->assertTrue($signalement->getDesordreCriteres()->contains($desordreCritere));
        $desordrePrecision = $this->desordrePrecisionRepository->findOneBy(
            ['desordrePrecisionSlug' => 'desordres_type_composition_logement_cuisine_collective_oui']
        );
        $this->assertTrue($signalement->getDesordrePrecisions()->contains($desordrePrecision));

        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => 'desordres_type_composition_logement_douche']
        );
        $this->assertTrue($signalement->getDesordreCriteres()->contains($desordreCritere));
        $desordrePrecision = $this->desordrePrecisionRepository->findOneBy(
            ['desordrePrecisionSlug' => 'desordres_type_composition_logement_douche_collective_non']
        );
        $this->assertTrue($signalement->getDesordrePrecisions()->contains($desordrePrecision));

        $desordreCritere = $this->desordreCritereRepository->findOneBy(
            ['slugCritere' => 'desordres_type_composition_logement_suroccupation']
        );
        $this->assertTrue($signalement->getDesordreCriteres()->contains($desordreCritere));
        $desordrePrecision = $this->desordrePrecisionRepository->findOneBy(
            ['desordrePrecisionSlug' => 'desordres_type_composition_logement_suroccupation_allocataire']
        );
        $this->assertTrue($signalement->getDesordrePrecisions()->contains($desordrePrecision));

        $this->entityManager->commit();
    }

    public function provideAllocataireCases(): \Generator
    {
        // certains cas ne sont pas sensés arriver
        yield 'oui - caf' => ['oui', 'caf', 'CAF'];
        yield 'oui - msa' => ['oui', 'msa', 'MSA'];
        yield 'oui - nsp' => ['oui', 'nsp', '1'];
        yield 'oui - null' => ['oui', null, '1'];
        yield 'oui - empty' => ['oui', '', '1'];

        yield 'non - caf' => ['non', 'caf', '0'];
        yield 'non - msa' => ['non', 'msa', '0'];
        yield 'non - nsp' => ['non', 'nsp', '0'];
        yield 'non - null' => ['non', null, '0'];
        yield 'non - empty' => ['non', '', '0'];

        yield 'nsp - caf' => ['nsp', 'caf', null];
        yield 'nsp - msa' => ['nsp', 'msa', null];
        yield 'nsp - nsp' => ['nsp', 'nsp', null];
        yield 'nsp - null' => ['nsp', null, null];
        yield 'nsp - empty' => ['nsp', '', null];

        yield 'null - caf' => [null, 'caf', null];
        yield 'null - msa' => [null, 'msa', null];
        yield 'null - nsp' => [null, 'nsp', null];
        yield 'null - null' => [null, null, null];
        yield 'null - empty' => [null, '', null];

        yield 'empty - caf' => ['', 'caf', null];
        yield 'empty - msa' => ['', 'msa', null];
        yield 'empty - nsp' => ['', 'nsp', null];
        yield 'empty - null' => ['', null, null];
        yield 'empty - empty' => ['', '', null];
    }

    /**
     * @dataProvider provideAllocataireCases
     */
    public function testResolveIsAllocataire(
        ?string $logementSocialAllocation,
        ?string $logementSocialCaisse,
        string|bool|null $expectedResult,
    ): void {
        $signalementDraftRequestMock = $this->createMock(SignalementDraftRequest::class);

        $signalementDraftRequestMock->method('getLogementSocialAllocation')
            ->willReturn($logementSocialAllocation);
        $signalementDraftRequestMock->method('getLogementSocialAllocationCaisse')
            ->willReturn($logementSocialCaisse);

        $reflection = new \ReflectionClass($this->signalementBuilder);
        $property = $reflection->getProperty('signalementDraftRequest');
        $property->setAccessible(true);
        $property->setValue($this->signalementBuilder, $signalementDraftRequestMock);

        $result = $this->invokeMethod($this->signalementBuilder, 'resolveIsAllocataire');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsConstructionAvant1949(): void
    {
        $this->assertNull($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', [null]));
        $this->assertTrue($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', ['1867']));
        $this->assertFalse($this->invokeMethod($this->signalementBuilder, 'isConstructionAvant1949', ['1949']));
    }

    /**
     * @param array<mixed> $parameters
     *
     * @throws \ReflectionException
     */
    private function invokeMethod(object &$object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
