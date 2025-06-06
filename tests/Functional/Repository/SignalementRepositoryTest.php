<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunkThrowException(): void
    {
        $this->expectException(NonUniqueResultException::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-1'
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindByReferenceChunk(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        $signalement = $signalementRepository->findByReferenceChunk(
            $territory,
            '2022-14'
        );

        $this->assertEquals('01', $signalement->getTerritory()->getZip());
    }

    public function testFindWithNoGeolocalisation(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $signalements = $signalementRepository->findWithNoGeolocalisation($territory);
        $this->assertEmpty($signalements);
        $signalements = $signalementRepository->findWithNoGeolocalisation();
        $this->assertEmpty($signalements);
    }

    public function testSignalementHasNDE(): void
    {
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-8']);

        $this->assertTrue($signalement->hasNDE());
    }

    public function testSignalementHasNotNDE(): void
    {
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-6']);

        $this->assertFalse($signalement->hasNDE());
    }

    public function testSignalementHasRSD(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-01']);

        $this->assertTrue($signalement->hasQualificaton(Qualification::RSD));
    }

    public function testSignalementHasNotRSD(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2024-02']);

        $this->assertTrue($signalement->hasQualificaton(Qualification::RSD));
    }

    public function testCountRefused(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsRefused = $signalementRepository->countRefused();
        $this->assertEquals(1, $signalementsRefused);
    }

    public function testCountCritereByZone(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $zones = $signalementRepository->countCritereByZone(null, null);
        $this->assertEquals(4, \count($zones));
        $this->assertArrayHasKey('critere_batiment_count', $zones);
        $this->assertArrayHasKey('critere_logement_count', $zones);
        $this->assertArrayHasKey('desordrecritere_batiment_count', $zones);
        $this->assertArrayHasKey('desordrecritere_logement_count', $zones);
    }

    public function testCountByDesordresCriteres(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $desordreCriteres = $signalementRepository->countByDesordresCriteres(null, null, null);
        $this->assertEquals(5, \count($desordreCriteres));
        $this->assertArrayHasKey('count', $desordreCriteres[0]);
        $this->assertArrayHasKey('labelCritere', $desordreCriteres[0]);
    }

    public function testCountSignalementUsagerAbandonProcedure(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsUsagerAbandonProcedure = $signalementRepository->countSignalementUsagerAbandonProcedure([]);
        $this->assertEquals(2, $signalementsUsagerAbandonProcedure);
    }

    public function testCountSignalementUsagerAbandonProcedure13(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsUsagerAbandonProcedure = $signalementRepository->countSignalementUsagerAbandonProcedure([$territory]);
        $this->assertEquals(1, $signalementsUsagerAbandonProcedure);
    }

    public function testFindAllArchived(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, null, null);
        $this->assertEquals(3, \count($signalementsArchived));
    }

    public function testFindAllArchivedTerritory(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, $territory, null);
        $this->assertEquals(1, \count($signalementsArchived));
    }

    public function testFindAllArchivedReference(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementsArchived = $signalementRepository->findAllArchived(1, 50, null, '2024-04');
        $this->assertEquals(1, \count($signalementsArchived));
    }

    public function testFindAllForEmailAndAddressWithValue(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(
            'francis.cabrel@astaffort.com',
            '3 rue Mars',
            '13015',
            'Marseille',
            false
        );
        $this->assertEquals(1, \count($emailExistingSignalements));
    }

    public function testFindAllForEmailAndAddressWithNullValue(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $emptyEmailExistingSignalements = $signalementRepository->findAllForEmailAndAddress(null, null, null, null);
        $this->assertEmpty($emptyEmailExistingSignalements);
    }

    /**
     * @dataProvider provideSearchWithGeoData
     * @param array<mixed> $options
     */
    public function testfindAllWithGeoData(string $email, array $options, int $nbResult): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalements = $signalementRepository->findAllWithGeoData($user, $options, 0);
        $this->assertCount($nbResult, $signalements);
    }

    public function provideSearchWithGeoData(): \Generator
    {
        yield 'Search all for super admin' => ['admin-01@signal-logement.fr', [], 47];
        yield 'Search in Marseille for super admin' => ['admin-01@signal-logement.fr', ['cities' => ['Marseille']], 25];
        yield 'Search all for admin partner multi territories' => ['admin-partenaire-multi-ter-13-01@signal-logement.fr', [], 6];
        yield 'Search in Ain for admin partner multi territories' => ['admin-partenaire-multi-ter-13-01@signal-logement.fr', ['territories' => 1], 1];
        yield 'Search all for user partner multi territories' => ['user-partenaire-multi-ter-34-30@signal-logement.fr', [], 2];
        yield 'Search in Hérault for user partner multi territories' => ['user-partenaire-multi-ter-34-30@signal-logement.fr', ['territories' => 35], 1];
    }

    public function testfindSignalementsLastSuiviWithSuiviAuto(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);

        $signalements = $signalementRepository->findSignalementsLastSuiviWithSuiviAuto($territory, 10);
        $this->assertCount(0, $signalements);
    }

    public function testfindSignalementsLastSuiviByPartnerOlderThan(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);

        $signalements = $signalementRepository->findSignalementsLastSuiviByPartnerOlderThan($territory, 10, 0);
        $this->assertCount(2, $signalements);
    }

    public function testfindOnSameAddress(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2025-09']);

        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress(
            $signalement,
            [],
            [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED, SignalementStatus::ARCHIVED]
        );
        $this->assertCount(1, $signalementsOnSameAddress);

        $new = new Signalement();
        $new->setAdresseOccupant($signalement->getAdresseOccupant());
        $new->setCpOccupant($signalement->getCpOccupant());
        $new->setVilleOccupant($signalement->getVilleOccupant());

        $signalementsOnSameAddress = $signalementRepository->findOnSameAddress($new);
        $this->assertCount(2, $signalementsOnSameAddress);
    }
}
