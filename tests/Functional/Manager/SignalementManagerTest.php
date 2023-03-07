<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Factory\SignalementFactory;
use App\Manager\SignalementManager;
use App\Service\Signalement\QualificationStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SignalementManagerTest extends KernelTestCase
{
    public const TERRITORY_13 = 13;

    private EntityManagerInterface $entityManager;
    private Security $security;
    private ManagerRegistry $managerRegistry;
    private SignalementFactory $signalementFactory;
    private EventDispatcherInterface $eventDispatcher;
    private QualificationStatusService $qualificationStatusService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        /* @var Security $security */
        $this->security = static::getContainer()->get('security.helper');
        /* @var SignalementFactory $signalementFactory */
        $this->signalementFactory = static::getContainer()->get(SignalementFactory::class);
        /* @var EventDispatcherInterface $eventDispatcher */
        $this->eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        /* @var QualificationStatusService $qualificationStatusService */
        $this->qualificationStatusService = static::getContainer()->get(QualificationStatusService::class);
    }

    public function testFindAllPartnersAffectedAndNotAffectedBySignalementLocalization()
    {
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $signalement = $signalementManager->findOneBy(['territory' => self::TERRITORY_13]);

        $partners = $signalementManager->findAllPartners($signalement);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(1, $partners['affected'], 'One partner should be affected');
        $this->assertCount(3, $partners['not_affected'], 'Three partners should not be affected');
    }

    public function testFindAllPartnersWithCompetences()
    {
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $signalement = $signalementManager->findOneBy(['reference' => '2023-8']);

        $partners = $signalementManager->findAllPartners($signalement, true);

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

        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $signalementClosed = $signalementManager->closeSignalementForAllPartners(
            $signalementActive,
            MotifCloture::LABEL['RESOLU']
        );

        $this->assertInstanceOf(Signalement::class, $signalementClosed);
        $this->assertEquals(Signalement::STATUS_CLOSED, $signalementClosed->getStatut());
        $this->assertEquals('Problème résolu', $signalementClosed->getMotifCloture());
        $this->assertInstanceOf(\DateTimeInterface::class, $signalementClosed->getClosedAt());

        $signalementHasAllAffectationsClosed = $signalementClosed->getAffectations()
            ->forAll(function (int $index, Affectation $affectation) {
                return Affectation::STATUS_CLOSED === $affectation->getStatut()
                && str_contains($affectation->getMotifCloture(), 'Problème résolu');
            });

        $this->assertTrue($signalementHasAllAffectationsClosed);
    }

    public function testCloseAffectation()
    {
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectationAccepted = $affectationRepository->findOneBy(['statut' => Affectation::STATUS_ACCEPTED]);
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $affectationClosed = $signalementManager->closeAffectation(
            $affectationAccepted,
            MotifCloture::LABEL['NON_DECENCE']
        );

        $this->assertEquals(Affectation::STATUS_CLOSED, $affectationClosed->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $affectationClosed->getAnsweredAt());
        $this->assertTrue(str_contains($affectationClosed->getMotifCloture(), 'Non décence'));
    }

    public function testFindEmailsAffectedToSignalement()
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        $signalement = $signalementRepository->findOneBy(['statut' => Signalement::STATUS_ACTIVE]);
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $emails = $signalementManager->findEmailsAffectedToSignalement($signalement);

        $this->assertGreaterThan(1, \count($emails));
    }

    public function testCreateSignalement(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        /** @var Territory $territory */
        $territory = $territoryRepository->findOneBy(['zip' => '01']);
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $signalement = $signalementManager->createOrUpdate(
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
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);
        $signalement = $signalementManager->createOrUpdate(
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
        $signalementManager = new SignalementManager($this->managerRegistry, $this->security, $this->signalementFactory, $this->eventDispatcher, $this->qualificationStatusService);

        $signalementImportedClone = clone $signalementImported;
        $signalement = $signalementManager->update(
            $signalementImportedClone,
            $this->getSignalementData(),
        );

        $this->assertTrue($signalement->getIsImported());
        $this->assertNotEquals(
            $signalementImported?->getModifiedAt()?->getTimestamp(),
            $signalement?->getModifiedAt()?->getTimestamp()
        );
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
            'dateVisite' => new \DateTimeImmutable(),
            'isOccupantPresentVisite' => true,
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
            'geoloc' => ['lat' => 5.386161, 'lng' => 43.312827],
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
            'mailSyndic' => $faker->companyEmail(),
            'telSyndic' => $faker->phoneNumber(),
            'nomSyndic' => $faker->company(),
            'nomSci' => $faker->company(),
            'nomRepresentantSci' => $faker->lastName().' '.$faker->firstName,
            'telSci' => $faker->phoneNumber(),
            'mailSci' => $faker->companyEmail(),
            'nbPiecesLogement' => $faker->randomDigit(),
            'nbChambresLogement' => $faker->randomDigit(),
            'nbNiveauxLogement' => $faker->randomDigit(),
        ];
    }
}
