<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Address;
use App\Entity\Territory;
use App\Repository\AddressRepository;
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
        /** @var AddressRepository $addressRepository */
        $addressRepository = $this->entityManager->getRepository(Address::class);

        // Test sans territoire - devrait retourner toutes les adresses
        $allAddresses = $addressRepository->findAllList();

        $this->assertIsArray($allAddresses);
        $this->assertNotEmpty($allAddresses);
        $this->assertCount(12, $allAddresses); // à ajuster quand fixtures adresses gérées dans la PR #6212

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
        /** @var AddressRepository $addressRepository */
        $addressRepository = $this->entityManager->getRepository(Address::class);
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);

        $territory = $territoryRepository->findOneBy(['zip' => '13']);
        $this->assertNotNull($territory, 'Territory with zip 13 should exist in fixtures');

        $addressesForTerritory = $addressRepository->findAllList($territory);

        $this->assertIsArray($addressesForTerritory);
        $this->assertCount(2, $addressesForTerritory); // à ajuster quand fixtures adresses gérées dans la PR #6212
    }
}
