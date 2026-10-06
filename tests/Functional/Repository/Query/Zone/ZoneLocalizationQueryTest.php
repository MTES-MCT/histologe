<?php

namespace App\Tests\Functional\Repository\Query\Zone;

use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\Query\Zone\ZoneLocalizationQuery;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZoneLocalizationQueryTest extends KernelTestCase
{
    private ZoneLocalizationQuery $zoneLocalizationQuery;
    private SignalementRepository $signalementRepository;
    private ZoneRepository $zoneRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->zoneLocalizationQuery = static::getContainer()->get(ZoneLocalizationQuery::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->zoneRepository = static::getContainer()->get(ZoneRepository::class);
    }

    public function testFindZoneIdsContainingPointInTwoZones(): void
    {
        // Signalement '2024-09' à La Bodinière, zone elle-même située dans la zone StMars
        $zoneIds = $this->findZoneIdsForSignalement('2024-09');
        sort($zoneIds);

        $expected = [
            $this->zoneRepository->findOneBy(['name' => 'StMars'])->getId(),
            $this->zoneRepository->findOneBy(['name' => 'La Bodinière'])->getId(),
        ];
        sort($expected);
        $this->assertSame($expected, $zoneIds);
    }

    public function testFindZoneIdsContainingPointInOneZone(): void
    {
        // Signalement '2024-11' à Saint-Mars du Désert, hors de La Bodinière
        $this->assertSame(
            [$this->zoneRepository->findOneBy(['name' => 'StMars'])->getId()],
            $this->findZoneIdsForSignalement('2024-11'),
        );
    }

    public function testFindZoneIdsContainingPointOutsideAnyZone(): void
    {
        // Signalement '2024-06' à Lunel, dans aucune zone
        $this->assertSame([], $this->findZoneIdsForSignalement('2024-06'));
    }

    public function testFindZoneIdsContainingPointIgnoresZonesOfOtherTerritories(): void
    {
        /** @var Territory $herault */
        $herault = static::getContainer()->get(TerritoryRepository::class)->findOneBy(['zip' => '34']);

        $this->assertSame([], $this->findZoneIdsForSignalement('2024-09', $herault));
    }

    /**
     * @return array<int>
     */
    private function findZoneIdsForSignalement(string $reference, ?Territory $territory = null): array
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => $reference]);

        return $this->zoneLocalizationQuery->findZoneIdsContainingPoint(
            $territory ?? $signalement->getAddress()->getTerritory(),
            (float) $signalement->getGeoloc()['lng'],
            (float) $signalement->getGeoloc()['lat'],
        );
    }
}
