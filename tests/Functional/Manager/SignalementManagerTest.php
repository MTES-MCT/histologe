<?php

namespace App\Tests\Functional\Manager;

use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Entity\Affectation;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\SignalementAffectationListViewFactory;
use App\Factory\SignalementExportFactory;
use App\Factory\SignalementFactory;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Service\Signalement\CriticiteCalculator;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use App\Service\Signalement\Qualification\QualificationStatusService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Specification\Signalement\SuroccupationSpecification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignalementManagerTest extends WebTestCase
{
    public const TERRITORY_13 = 13;

    private EntityManagerInterface $entityManager;
    private Security $security;
    private ManagerRegistry $managerRegistry;
    private SignalementFactory $signalementFactory;
    private EventDispatcherInterface $eventDispatcher;
    private QualificationStatusService $qualificationStatusService;
    private SignalementAffectationListViewFactory $signalementAffectationListViewFactory;
    private SignalementExportFactory $signalementExportFactory;
    private ParameterBagInterface $parameterBag;
    private SignalementManager $signalementManager;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private SuroccupationSpecification $suroccupationSpecification;
    private CriticiteCalculator $criticiteCalculator;
    private SignalementQualificationUpdater $signalementQualificationUpdater;
    private DesordrePrecisionRepository $desordrePrecisionRepository;
    private DesordreCritereRepository $desordreCritereRepository;
    private DesordreCompositionLogementLoader $desordreCompositionLogementLoader;
    private SuiviManager $suiviManager;

    protected function setUp(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $this->security = static::getContainer()->get('security.helper');
        $this->signalementFactory = static::getContainer()->get(SignalementFactory::class);
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        /* @var QualificationStatusService $qualificationStatusService */
        $this->qualificationStatusService = static::getContainer()->get(QualificationStatusService::class);
        $this->signalementAffectationListViewFactory = static::getContainer()->get(
            SignalementAffectationListViewFactory::class
        );
        $this->signalementExportFactory = static::getContainer()->get(SignalementExportFactory::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->csrfTokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
        $this->suroccupationSpecification = static::getContainer()->get(SuroccupationSpecification::class);
        $this->criticiteCalculator = static::getContainer()->get(CriticiteCalculator::class);
        $this->signalementQualificationUpdater = static::getContainer()->get(SignalementQualificationUpdater::class);
        $this->desordrePrecisionRepository = static::getContainer()->get(DesordrePrecisionRepository::class);
        $this->desordreCritereRepository = static::getContainer()->get(DesordreCritereRepository::class);
        $this->desordreCompositionLogementLoader = static::getContainer()->get(DesordreCompositionLogementLoader::class);
        $this->suiviManager = static::getContainer()->get(SuiviManager::class);

        $this->signalementManager = new SignalementManager(
            $this->managerRegistry,
            $this->security,
            $this->signalementFactory,
            $this->eventDispatcher,
            $this->qualificationStatusService,
            $this->signalementAffectationListViewFactory,
            $this->signalementExportFactory,
            $this->parameterBag,
            $this->csrfTokenManager,
            $this->suroccupationSpecification,
            $this->criticiteCalculator,
            $this->signalementQualificationUpdater,
            $this->desordrePrecisionRepository,
            $this->desordreCritereRepository,
            $this->desordreCompositionLogementLoader,
            $this->suiviManager,
        );
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);
    }

    public function testFindAllPartnersAffectedAndNotAffectedBySignalementLocalization()
    {
        $signalement = $this->signalementManager->findOneBy(['territory' => self::TERRITORY_13]);
        $partners = $this->signalementManager->findAllPartners($signalement);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(1, $partners['affected'], 'One partner should be affected');
        $this->assertCount(5, $partners['not_affected'], 'Five partners should not be affected');
    }

    public function testFindAllPartnersWithCompetences()
    {
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-8']);

        $partners = $this->signalementManager->findAllPartners($signalement, true);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(0, $partners['affected'], '0 partner should be affected');
        $this->assertCount(19, $partners['not_affected'], '19 partners should not be affected');
        $firstPartner = $partners['not_affected'][0];
        $this->assertArrayHasKey('competence', $firstPartner);
    }

    public function testCloseSignalementForAllPartners()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementActive = $signalementRepository->findOneBy(['statut' => Signalement::STATUS_ACTIVE]);

        $signalementClosed = $this->signalementManager->closeSignalementForAllPartners(
            $signalementActive,
            MotifCloture::tryFrom('TRAVAUX_FAITS_OU_EN_COURS')
        );

        $this->assertInstanceOf(Signalement::class, $signalementClosed);
        $this->assertEquals(Signalement::STATUS_CLOSED, $signalementClosed->getStatut());
        $this->assertEquals('Travaux faits ou en cours', $signalementClosed->getMotifCloture()->label());
        $this->assertInstanceOf(\DateTimeInterface::class, $signalementClosed->getClosedAt());

        $signalementHasAllAffectationsClosed = $signalementClosed->getAffectations()
            ->forAll(function (int $index, Affectation $affectation) {
                return Affectation::STATUS_CLOSED === $affectation->getStatut()
                && str_contains($affectation->getMotifCloture()->label(), 'Travaux faits ou en cours'); // TODO ??
            });

        $this->assertTrue($signalementHasAllAffectationsClosed);
    }

    public function testFindEmailsAffectedToSignalement()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $signalement = $signalementRepository->findOneBy(['statut' => Signalement::STATUS_ACTIVE]);
        $emails = $this->signalementManager->findEmailsAffectedToSignalement($signalement);

        $this->assertGreaterThan(1, \count($emails));
    }

    public function testCreateSignalement(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        /** @var Territory $territory */
        $territory = $territoryRepository->findOneBy(['zip' => '01']);
        $signalement = $this->signalementManager->createOrUpdate(
            $territory,
            $this->getSignalementData('2023-2'),
            true
        );

        $this->assertInstanceOf(Signalement::class, $signalement);
    }

    public function testUpdateSignalement(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        /** @var Territory $territory */
        $territory = $territoryRepository->findOneBy(['zip' => '01']);
        $signalement = $this->signalementManager->createOrUpdate(
            $territory,
            $this->getSignalementData('2023-1'),
            true
        );

        $this->assertInstanceOf(Signalement::class, $signalement);
        $this->assertEquals('2023-1', $signalement->getReference());
    }

    public function testUpdateSignalementImported(): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var Signalement $signalementImported */
        $signalementImported = $signalementRepository->findOneBy(['isImported' => true]);

        $signalementImportedClone = clone $signalementImported;
        $signalement = $this->signalementManager->update(
            $signalementImportedClone,
            $this->getSignalementData(),
        );

        $this->assertTrue($signalement->getIsImported());
        $this->assertNotEquals(
            $signalementImported?->getModifiedAt()?->getTimestamp(),
            $signalement?->getModifiedAt()?->getTimestamp()
        );
    }

    public function testUpdateFromEmptyCompositionLogementRequest(): void
    {
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-8']);
        $emptyCompositionLogementRequest = new CompositionLogementRequest(
            type: '',
            typeLogementNatureAutrePrecision: '',
            typeCompositionLogement: '',
            superficie: '',
            compositionLogementHauteur: '',
            compositionLogementNbPieces: '',
            nombreEtages: '',
            typeLogementRdc: '',
            typeLogementDernierEtage: '',
            typeLogementSousCombleSansFenetre: '',
            typeLogementSousSolSansFenetre: '',
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

    public function testGetPhotosBySlug(): void
    {
        $signalement = $this->signalementManager->findOneBy(['reference' => '2023-27']);

        $desordrePrecisionSlug = 'desordres_batiment_proprete_interieur';
        $photos = $this->signalementManager->getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(1, $photos);
        $firstKey = array_keys($photos)[0];
        $this->assertEquals(DocumentType::SITUATION, $photos[$firstKey]->getDocumentType());
        $this->assertEquals('Capture-d-ecran-du-2023-06-13-12-58-43-648b2a6b9730f.png', $photos[$firstKey]->getTitle());

        $desordrePrecisionSlug = 'desordres_batiment_isolation_murs';
        $photos = $this->signalementManager->getPhotosBySlug($signalement, $desordrePrecisionSlug);
        $this->assertCount(0, $photos);
    }

    private function getSignalementData(string $reference = null): array
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
            'typeLogement' => 'maison',
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
            'modeContactProprio' => ['sms'],
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
