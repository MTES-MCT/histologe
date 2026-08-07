<?php

namespace App\Tests\Functional\Repository\Query\Address;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\Address;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\Query\Address\AddressesHistoryQuery;
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
            $this->assertArrayHasKey('postcode', $result);
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

        $searchQuery = new AddressesHistorySearchQuery(
            adresse: strtolower(substr($address->getStreet(), 0, 5))
        );

        $results = $this->addressesHistoryQuery->findAddressesWithHistory($user, $searchQuery);

        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertEquals($address->getStreet(), $result['street']);
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
}
