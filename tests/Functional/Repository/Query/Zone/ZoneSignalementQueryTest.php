<?php

namespace App\Tests\Functional\Repository\Query\Zone;

use App\Entity\Zone;
use App\Repository\Query\Zone\ZoneSignalementQuery;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneSignalementQueryTest extends KernelTestCase
{
    private ZoneSignalementQuery $zoneSignalementQuery;
    private ZoneRepository $zoneRepository;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;
    private TerritoryRepository $territoryRepository;

    protected function setUp(): void
    {
        $this->zoneSignalementQuery = static::getContainer()->get(ZoneSignalementQuery::class);
        $this->zoneRepository = static::getContainer()->get(ZoneRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);
    }

    public function testFindSignalementsByZone(): void
    {
        $zone = $this->zoneRepository->findOneBy(['name' => 'Permis louer Agde']);
        $this->assertNotNull($zone, 'La zone "Permis louer Agde" doit exister dans les fixtures');

        $result = $this->zoneSignalementQuery->findSignalementsByZone($zone);

        $this->assertIsArray($result);
        $this->assertCount(1, $result, 'La zone doit contenir exactement un signalement');
        $this->assertNotEmpty($result, 'La zone doit retourner au moins un signalement');

        $first = $result[0];

        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('reference', $first);
        $this->assertArrayHasKey('housenumber', $first);
        $this->assertArrayHasKey('street', $first);
        $this->assertArrayHasKey('post_code', $first);
        $this->assertArrayHasKey('city', $first);
        $this->assertArrayHasKey('lat', $first);
        $this->assertArrayHasKey('lng', $first);
    }

    public function testFindZonesBySignalement(): void
    {
        $zone = $this->zoneRepository->findOneBy(['name' => 'Permis louer Agde']);
        $signalementsInZone = $this->zoneSignalementQuery->findSignalementsByZone($zone);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $signalementsInZone[0]['uuid']]);

        $result = $this->zoneSignalementQuery->findZonesBySignalement($signalement);

        $this->assertSame([['name' => 'Permis louer Agde']], $result);
    }

    public function testFindZonesBySignalementWithoutZone(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);

        $result = $this->zoneSignalementQuery->findZonesBySignalement($signalement);

        $this->assertSame([], $result);
    }

    public function testFindForUserAndTerritoryAsSuperAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $result = $this->zoneSignalementQuery->findForUserAndTerritory($user, null);

        $this->assertSame(['La Bodinière', 'Permis louer Agde', 'StMars'], $this->getZoneNames($result));
    }

    public function testFindForUserAndTerritoryAsSuperAdminWithTerritory(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $territory = $this->territoryRepository->findOneBy(['zip' => '44']);

        $result = $this->zoneSignalementQuery->findForUserAndTerritory($user, $territory);

        $this->assertSame(['La Bodinière', 'StMars'], $this->getZoneNames($result));
    }

    public function testFindForUserAndTerritoryAsTerritoryAdmin(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);

        $result = $this->zoneSignalementQuery->findForUserAndTerritory($user, null);

        $this->assertSame(['La Bodinière', 'StMars'], $this->getZoneNames($result));
    }

    public function testFindForUserAndTerritoryAsTerritoryAdminOnOtherTerritory(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-44-01@signal-logement.fr']);
        $territory = $this->territoryRepository->findOneBy(['zip' => '34']);

        $result = $this->zoneSignalementQuery->findForUserAndTerritory($user, $territory);

        $this->assertSame([], $result);
    }

    /**
     * @param array<int, Zone> $zones
     *
     * @return array<int, string>
     */
    private function getZoneNames(array $zones): array
    {
        return array_map(static fn (Zone $zone) => $zone->getName(), $zones);
    }
}
