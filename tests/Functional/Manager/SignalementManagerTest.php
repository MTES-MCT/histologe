<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Dto\SignalementAffectationClose;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\SignalementAffectationListViewFactory;
use App\Factory\SignalementExportFactory;
use App\Factory\SignalementFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\BailleurRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\Query\SignalementList\ExportIterableQuery;
use App\Repository\Query\SignalementList\ListPaginatorQuery;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\SignalementAddressUpdater;
use App\Service\Signalement\ZipcodeProvider;
use App\Specification\Signalement\SuroccupationSpecification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignalementManagerTest extends WebTestCase
{
    public const int TERRITORY_13 = 13;

    private EntityManagerInterface $entityManager;
    private Security $security;
    private ManagerRegistry $managerRegistry;
    private SignalementFactory $signalementFactory;
    private QualificationStatusService $qualificationStatusService;
    private SignalementAffectationListViewFactory $signalementAffectationListViewFactory;
    private SignalementExportFactory $signalementExportFactory;
    private SignalementManager $signalementManager;
    private SuroccupationSpecification $suroccupationSpecification;
    private CriticiteCalculator $criticiteCalculator;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private DesordrePrecisionRepository $desordrePrecisionRepository;
    private DesordreCompositionLogementLoader $desordreCompositionLogementLoader;
    private SuiviManager $suiviManager;
    private BailleurRepository $bailleurRepository;
    private SignalementAddressUpdater $signalementAddressUpdater;
    private AffectationManager $affectationManager;
    private ZipcodeProvider $zipcodeProvider;
    private ExportIterableQuery $exportIterableQuery;
    private ListPaginatorQuery $listPaginatorQuery;
    private HtmlSanitizerInterface $htmlSanitizerInterface;

    protected function setUp(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $em;
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->signalementFactory = static::getContainer()->get(SignalementFactory::class);
        /* @var QualificationStatusService $qualificationStatusService */
        $this->qualificationStatusService = static::getContainer()->get(QualificationStatusService::class);
        $this->signalementAffectationListViewFactory = static::getContainer()->get(
            SignalementAffectationListViewFactory::class
        );
        $this->signalementExportFactory = static::getContainer()->get(SignalementExportFactory::class);
        $this->suroccupationSpecification = static::getContainer()->get(SuroccupationSpecification::class);
        $this->criticiteCalculator = static::getContainer()->get(CriticiteCalculator::class);
        $this->signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        $this->desordrePrecisionRepository = static::getContainer()->get(DesordrePrecisionRepository::class);
        $this->desordreCompositionLogementLoader = static::getContainer()->get(DesordreCompositionLogementLoader::class);
        $this->suiviManager = static::getContainer()->get(SuiviManager::class);
        $this->bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $this->signalementAddressUpdater = static::getContainer()->get(SignalementAddressUpdater::class);
        $this->affectationManager = static::getContainer()->get(AffectationManager::class);
        $this->zipcodeProvider = static::getContainer()->get(ZipcodeProvider::class);
        $this->exportIterableQuery = static::getContainer()->get(ExportIterableQuery::class);
        $this->listPaginatorQuery = static::getContainer()->get(ListPaginatorQuery::class);
        $this->htmlSanitizerInterface = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');

        $this->signalementManager = new SignalementManager(
            $this->managerRegistry,
            $this->security,
            $this->signalementFactory,
            $this->qualificationStatusService,
            $this->signalementAffectationListViewFactory,
            $this->signalementExportFactory,
            $this->suroccupationSpecification,
            $this->criticiteCalculator,
            $this->signalementQualificationUpdater,
            $this->desordrePrecisionRepository,
            $this->desordreCompositionLogementLoader,
            $this->suiviManager,
            $this->bailleurRepository,
            $this->signalementAddressUpdater,
            $this->zipcodeProvider,
            $this->exportIterableQuery,
            $this->listPaginatorQuery,
            $this->htmlSanitizerInterface,
        );
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
    }

    public function testFindAffectablePartnersAffectedAndNotAffectedBySignalementLocalization(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->findOneBy(['territory' => self::TERRITORY_13]);
        $partners = $this->signalementManager->findAffectablePartners($signalement);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(1, $partners['affected'], 'One partner should be affected');
        $this->assertCount(9, $partners['not_affected'], 'Nine partners should not be affected');
    }

    public function testCloseSignalementForAllPartners(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementActive = $signalementRepository->findOneBy(['statut' => SignalementStatus::ACTIVE->value]);

        $signalementAffectationClose = (new SignalementAffectationClose())
            ->setSignalement($signalementActive)
            ->setMotifCloture(MotifCloture::tryFrom('TRAVAUX_FAITS_OU_EN_COURS'))
            ->setDescription('Tous les problèmes ont été résolus en moins de 15 ans');
        $affectation = $signalementActive->getAffectations()->first() ?: null;
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $signalementClosed = $this->signalementManager->closeSignalement($signalementAffectationClose);
        /** @var User $user */
        $user = $this->security->getUser();
        $this->affectationManager->closeBySignalement(
            $signalementClosed,
            $signalementAffectationClose->getMotifCloture(),
            $user,
            $affectation->getPartner()
        );

        $this->assertEquals($signalementActive->getComCloture(), 'Tous les problèmes ont été résolus en moins de 15 ans');
        $this->assertEquals($signalementClosed->getComCloture(), 'Tous les problèmes ont été résolus en moins de 15 ans');

        $this->assertInstanceOf(Signalement::class, $signalementClosed);
        $this->assertEquals(SignalementStatus::CLOSED, $signalementClosed->getStatut());
        $this->assertEquals('Travaux faits ou en cours', $signalementClosed->getMotifCloture()->label());
        $this->assertInstanceOf(\DateTimeInterface::class, $signalementClosed->getClosedAt());

        $signalementHasAllAffectationsClosed = $signalementClosed->getAffectations()
            ->forAll(function (int $index, Affectation $affectation) {
                return AffectationStatus::CLOSED === $affectation->getStatut()
                && str_contains($affectation->getMotifCloture()->label(), 'Travaux faits ou en cours'); // TODO ??
            });

        $this->assertTrue($signalementHasAllAffectationsClosed);
    }

    public function testCreateSignalement(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        /** @var Territory $territory */
        $territory = $territoryRepository->findOneBy(['zip' => '01']);
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->createOrUpdateFromArrayForImport(
            $territory,
            $this->getSignalementData('2023-2')
        );

        $this->assertInstanceOf(Signalement::class, $signalement);
    }

    public function testUpdateSignalement(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        /** @var Territory $territory */
        $territory = $territoryRepository->findOneBy(['zip' => '01']);
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->createOrUpdateFromArrayForImport(
            $territory,
            $this->getSignalementData('2023-1')
        );

        $this->assertInstanceOf(Signalement::class, $signalement);
        $this->assertEquals('2023-1', $signalement->getReference());
    }

    public function testUpdateSignalementImported(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalementImported */
        $signalementImported = $signalementRepository->findOneBy(['isImported' => true]);

        $signalementImportedClone = clone $signalementImported;
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->update(
            $signalementImportedClone,
            $this->getSignalementData(),
        );

        $this->assertTrue($signalement->getIsImported());
        $this->assertNotEquals(
            $signalementImported->getModifiedAt()?->getTimestamp(),
            $signalement->getModifiedAt()?->getTimestamp()
        );
    }

    public function testUpdateFromEmptyCompositionLogementRequest(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-8']);
        $emptyCompositionLogementRequest = new CompositionLogementRequest(
            type: '',
            typeLogementNatureAutrePrecision: '',
            typeCompositionLogement: '',
            superficie: '',
            compositionLogementNbPieces: '',
            nombreEtages: '',
            etage: '',
            avecFenetres: '',
            typeLogementCommoditesPieceAVivre9m: '',
            typeLogementCommoditesCuisine: '',
            typeLogementCommoditesCuisineCollective: '',
            typeLogementCommoditesSalleDeBain: '',
            typeLogementCommoditesSalleDeBainCollective: '',
            typeLogementCommoditesWc: '',
            typeLogementCommoditesWcCollective: '',
            typeLogementCommoditesWcCuisine: '',
        );

        $this->signalementManager->updateFromCompositionLogementRequest(
            $signalement,
            $emptyCompositionLogementRequest,
        );
        $this->assertNull($signalement->getSuperficie());

        $emptyCompositionLogementRequest = new CompositionLogementRequest();
        $this->signalementManager->updateFromCompositionLogementRequest(
            $signalement,
            $emptyCompositionLogementRequest,
        );
        $this->assertNull($signalement->getSuperficie());

        $emptyCompositionLogementRequest = new CompositionLogementRequest(
            superficie: 'neuf',
        );
        $this->signalementManager->updateFromCompositionLogementRequest(
            $signalement,
            $emptyCompositionLogementRequest,
        );
        $this->assertNull($signalement->getSuperficie());

        $emptyCompositionLogementRequest = new CompositionLogementRequest(
            superficie: '9.9',
        );
        $this->signalementManager->updateFromCompositionLogementRequest(
            $signalement,
            $emptyCompositionLogementRequest,
        );
        $this->assertEquals($signalement->getSuperficie(), 9.9);

        /** @var ValidatorInterface $validator */
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $errors = $validator->validate($emptyCompositionLogementRequest, null, ['Default', 'LOCATAIRE']);
        $this->assertCount(8, $errors);
        /** @var ConstraintViolationList $errors */
        $errorsAsString = (string) $errors;
        $this->assertStringContainsString('Merci de définir le nombre de pièces à vivre', $errorsAsString);
    }

    public function testUpdateFromSignalementQualificationWithNdeRequest(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()->first();
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: '1970-01-01',
            dateDernierDPE: '2023-01-02',
            superficie: 50,
            consommationEnergie: 10000,
            dpe: true
        );
        $this->signalementManager->updateFromSignalementQualification($signalementQualification, $qualificationNDERequest);
        $this->assertEquals('1970-01-01', $signalement->getDateEntree()->format('Y-m-d'));
        $this->assertEquals(50, $signalement->getSuperficie());
    }

    public function testUpdateFromSignalementQualificationWithNullNdeRequest(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-8']);
        /** @var SignalementQualification $signalementQualification */
        $signalementQualification = $signalement->getSignalementQualifications()->first();
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: null,
            dateDernierDPE: null,
            superficie: null,
            consommationEnergie: null,
            dpe: null
        );
        $this->signalementManager->updateFromSignalementQualification($signalementQualification, $qualificationNDERequest);
        $this->assertEquals('2023-01-08', $signalement->getDateEntree()->format('Y-m-d'));
        $this->assertEquals(100, $signalement->getSuperficie());
    }

    /**
     * @return array<mixed>
     */
    private function getSignalementData(?string $reference = null): array
    {
        $faker = Factory::create('fr_FR');

        return [
            'reference' => $reference ?? (new \DateTimeImmutable())->format('Y').'-1',
            'createdAt' => new \DateTimeImmutable(),
            'closedAt' => new \DateTimeImmutable(),
            'motifCloture' => null,
            'photos' => null,
            'documents' => null,
            'details' => $faker->realText(),
            'isProprioAverti' => false,
            'prorioAvertiAt' => new \DateTimeImmutable(),
            'nbAdultes' => $faker->randomDigit(),
            'nbEnfantsM6' => $faker->randomDigit(),
            'nbEnfantsP6' => $faker->randomDigit(),
            'nbOccupantsLogement' => $faker->randomDigit(),
            'isAllocataire' => true,
            'numAllocataire' => $faker->randomNumber(7),
            'superficie' => $faker->numberBetween(30, 100),
            'nomProprio' => $faker->lastName(),
            'adresseProprio' => $faker->streetAddress(),
            'telProprio' => $faker->phoneNumber(),
            'mailProprio' => $faker->email(),
            'isLogementSocial' => true,
            'isPreavisDepart' => false,
            'isRelogement' => false,
            'isNotOccupant' => false,
            'nomDeclarant' => $faker->lastName(),
            'prenomDeclarant' => $faker->firstName(),
            'telDeclarant' => $faker->phoneNumber(),
            'mailDeclarant' => $faker->email(),
            'lienDeclarantOccupant' => 'PROCHE',
            'structureDeclarant' => null,
            'nomOccupant' => $faker->firstName(),
            'prenomOccupant' => $faker->firstName(),
            'telOccupant' => $faker->phoneNumber(),
            'mailOccupant' => $faker->email(),
            'adresseOccupant' => $faker->address(),
            'cpOccupant' => $faker->postcode(),
            'villeOccupant' => $faker->city(),
            'inseeOccupant' => $faker->postcode(),
            'etageOccupant' => $faker->randomDigit(),
            'escalierOccupant' => $faker->randomDigit(),
            'numAppartOccupant' => $faker->randomDigit(),
            'isRsa' => false,
            'isConstructionAvant1949' => false,
            'isFondSolidariteLogement' => false,
            'isRisqueSurOccupation' => false,
            'numeroInvariant' => null,
            'natureLogement' => 'maison',
            'loyer' => $faker->numberBetween(300, 1000),
            'isBailEnCours' => true,
            'dateEntree' => new \DateTimeImmutable(),
            'isRefusIntervention' => false,
            'raisonRefusIntervention' => null,
            'isCguAccepted' => true,
            'modifiedAt' => null,
            'statut' => null,
            'geoloc' => ['lat' => 43.312827, 'lng' => 5.386161],
            'montantAllocation' => null,
            'codeProcedure' => null,
            'adresseAutreOccupant' => null,
            'isConsentementTiers' => true,
            'anneeConstruction' => '1995',
            'typeEnergieLogement' => null,
            'origineSignalement' => null,
            'situationOccupant' => null,
            'situationProOccupant' => null,
            'naissanceOccupants' => null,
            'isLogementCollectif' => false,
            'nomReferentSocial' => null,
            'StructureReferentSocial' => null,
            'nbPiecesLogement' => $faker->randomDigit(),
            'nbChambresLogement' => $faker->randomDigit(),
            'nbNiveauxLogement' => $faker->randomDigit(),
        ];
    }
}
