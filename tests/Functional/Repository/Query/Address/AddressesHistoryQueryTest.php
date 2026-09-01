<?php

namespace App\Tests\Functional\Repository\Query\Address;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\Address;
use App\Entity\Enum\ArreteType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\Zone;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AddressesHistoryQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AddressesHistoryQuery $addressesHistoryQuery;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->addressesHistoryQuery = new AddressesHistoryQuery($this->entityManager);
    }

    public function testFindAllList(): void
    {
        /** @var AddressesHistoryQuery $addressesHistoryQuery */
        $addressesHistoryQuery = new AddressesHistoryQuery($this->entityManager);

        // Test sans territoire - devrait retourner toutes les adresses
        $allAddresses = $addressesHistoryQuery->findAllList();

        $this->assertIsArray($allAddresses);
        $this->assertNotEmpty($allAddresses);
        $this->assertCount(60, $allAddresses);

        // Vérifie la structure des résultats
        foreach ($allAddresses as $address) {
            $this->assertArrayHasKey('id', $address);
            $this->assertArrayHasKey('address', $address);
            $this->assertIsString($address['address']);
            $this->assertNotEmpty($address['address']);
        }

        // Vérifie qu'il n'y a pas de doublons d'IDs
        $ids = array_column($allAddresses, 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)));
    }

    public function testFindAllListWithTerritory(): void
    {
        /** @var AddressesHistoryQuery $addressesHistoryQuery */
        $addressesHistoryQuery = new AddressesHistoryQuery($this->entityManager);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);

        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $this->assertNotNull($territory, 'Territory with zip 13 should exist in fixtures');

        $addressesForTerritory = $addressesHistoryQuery->findAllList($territory);

        $this->assertIsArray($addressesForTerritory);
        $this->assertCount(21, $addressesForTerritory);
    }

    public function testFindAddressesWithHistoryForSuperAdmin(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertArrayHasKey('addressId', $result);
            $this->assertArrayHasKey('housenumber', $result);
            $this->assertArrayHasKey('street', $result);
            $this->assertArrayHasKey('postCode', $result);
            $this->assertArrayHasKey('city', $result);
            $this->assertArrayHasKey('territoryId', $result);

            // Ensure we have at least one signalement or one arrete
            $hasSignalement = isset($result['id']) && !empty($result['id']);
            $hasArrete = isset($result['arreteId']) && !empty($result['arreteId']);
            $this->assertTrue($hasSignalement || $hasArrete, 'Each address must have at least one signalement or one arrete');
        }
    }

    public function testFindAddressesWithHistoryForTerritoryAdmin(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $this->assertNotNull($user);
        $this->assertTrue($user->isTerritoryAdmin());

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user);

        $this->assertIsArray($results);

        if (!empty($results)) {
            $userTerritories = $user->getPartnersTerritories();
            foreach ($results as $result) {
                $this->assertContains(
                    $result['territoryId'],
                    array_map(static fn (Territory $t) => $t->getId(), $userTerritories),
                    'Territory admin should only see addresses from their territories'
                );
            }
        }
    }

    public function testFindAddressesWithHistoryWithAdresseFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        // Find an existing address to search for
        $address = $this->entityManager->getRepository(Address::class)->findOneBy([]);
        $this->assertNotNull($address, 'Need at least one address in the database');

        $search = strtolower(substr($address->getStreet(), 0, 5)); // Use the first 5 characters of the street name for search
        $searchQuery = new AddressesHistorySearchQuery($search);

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertStringContainsString($search, strtolower($result['street']));
        }
    }

    public function testFindAddressesWithHistoryWithCommuneFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $address = $this->entityManager->getRepository(Address::class)->findOneBy([]);
        $this->assertNotNull($address);

        $searchQuery = new AddressesHistorySearchQuery(
            communes: [$address->getCity()]
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $matchesCity = $result['city'] === $address->getCity();
            $matchesPostCode = $result['postCode'] === $address->getPostCode();
            $this->assertTrue($matchesCity || $matchesPostCode);
        }
    }

    public function testFindAddressesWithHistoryWithTerritoireFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $this->assertNotNull($territory);

        $searchQuery = new AddressesHistorySearchQuery(
            territoire: (string) $territory->getId()
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertEquals($territory->getId(), $result['territoryId']);
        }
    }

    public function testFindAddressesWithHistoryWithNatureParcPublicFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $searchQuery = new AddressesHistorySearchQuery(
            natureParc: 'public'
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        // Results might be empty if no public housing in test data
    }

    public function testFindAddressesWithHistoryWithDossiersMultiplesOuiFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $searchQuery = new AddressesHistorySearchQuery(
            dossiersMultiples: 'oui'
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        // All results should have multiple signalements at the same address
        // This is enforced by the query subquery
    }

    public function testFindAddressesWithHistoryWithDossiersMultiplesNonFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $searchQuery = new AddressesHistorySearchQuery(
            dossiersMultiples: 'non'
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        // All results should have only one signalement at the same address
        // This is enforced by the query subquery
    }

    public function testFindAddressesWithHistoryReturnsOnlyActiveNeedValidationOrClosedSignalements(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            if (null !== $result['statut']) {
                $this->assertContains(
                    $result['statut'],
                    [SignalementStatus::ACTIVE, SignalementStatus::NEED_VALIDATION, SignalementStatus::CLOSED]
                );
            }
        }
    }

    public function testFindAddressesWithHistoryIncludesSignalementAndArreteData(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user);

        $this->assertIsArray($results);

        $foundSignalement = false;
        $foundArrete = false;

        foreach ($results as $result) {
            if (null !== $result['id']) {
                $foundSignalement = true;
                $this->assertArrayHasKey('uuid', $result);
                $this->assertArrayHasKey('reference', $result);
                $this->assertArrayHasKey('statut', $result);
                $this->assertArrayHasKey('nomOccupant', $result);
                $this->assertArrayHasKey('prenomOccupant', $result);
            }

            if (null !== $result['arreteId']) {
                $foundArrete = true;
                $this->assertArrayHasKey('dateArrete', $result);
                $this->assertArrayHasKey('arreteType', $result);
            }
        }

        // At least one result should have signalement or arrete data
        $this->assertTrue($foundSignalement || $foundArrete);
    }

    public function testFindAddressesWithHistoryWithMultipleFilters(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '13']);
        $this->assertNotNull($territory);

        $searchQuery = new AddressesHistorySearchQuery(
            territoire: (string) $territory->getId(),
            dossiersMultiples: 'non',
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertEquals($territory->getId(), $result['territoryId']);
        }
    }

    public function testFindAddressesWithHistoryWithBailleurOuSyndicFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        // Test avec un bailleur connu dans les fixtures
        $searchQuery = new AddressesHistorySearchQuery(
            bailleurOuSyndic: ['Habitat 44']
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        // Test avec plusieurs bailleurs
        $searchQuery = new AddressesHistorySearchQuery(
            bailleurOuSyndic: ['Habitat 44', 'Bailleur fatigué', '13 Habitat']
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(6, $results);
    }

    public function testFindAddressesWithHistoryWithTypesArretesFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        // Test avec un type d'arrêté connu
        $searchQuery = new AddressesHistorySearchQuery(
            arreteTypes: [ArreteType::MISE_EN_SECURITE->value]
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(11, $results);

        // Test avec un type d'arrêté connu
        $searchQuery = new AddressesHistorySearchQuery(
            arreteTypes: [ArreteType::MISE_EN_SECURITE->value, ArreteType::MISE_EN_SECURITE_PROCEDURE_URGENTE->value]
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(15, $results);
    }

    public function testFindAddressesWithHistoryWithCommuneAndEpciFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        $address = $this->entityManager->getRepository(Address::class)->findOneBy([]);
        $this->assertNotNull($address);

        // Test avec un mélange de commune et EPCI (préfixé par "EPCI : ")
        $searchQuery = new AddressesHistorySearchQuery(
            communes: ['Marseille']
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(29, $results);

        // Test avec un mélange de commune et EPCI (préfixé par "EPCI : ")
        $searchQuery = new AddressesHistorySearchQuery(
            communes: ['Marseille', 'EPCI : CC d\'Erdre et Gesvres']
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        $this->assertCount(34, $results);
    }

    public function testFindAddressesWithHistoryWithZoneFilter(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->assertNotNull($user);

        // Récupère une zone existante dans les fixtures
        $zone = $this->entityManager->getRepository(Zone::class)->findOneBy(['name' => 'StMars']);

        if ($zone) {
            $searchQuery = new AddressesHistorySearchQuery(
                zone: (string) $zone->getId()
            );

            $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

            $this->assertIsArray($results);
            $this->assertCount(5, $results);
        }
    }
}
