<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Territory;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AddressRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    public function testFindAllList(): void
    {
        /** @var AddressesHistoryQuery $addressesHistoryQuery */
        $addressesHistoryQuery = new AddressesHistoryQuery($this->entityManager);

        // Test sans territoire - devrait retourner toutes les adresses
        $allAddresses = $addressesHistoryQuery->findAllList();

        $this->assertIsArray($allAddresses);
        $this->assertNotEmpty($allAddresses);
        $this->assertCount(12, $allAddresses); // TODO : ajuster quand fixtures adresses gérées dans la PR #6212

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
        $this->assertCount(2, $addressesForTerritory); // TODO : ajuster quand fixtures adresses gérées dans la PR #6212
    }
}
