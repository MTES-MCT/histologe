<?php

namespace App\DataFixtures\Loader;

use App\Entity\Zone;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadZoneData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $zoneRows = Yaml::parseFile(__DIR__.'/../Files/Zone.yml');
        foreach ($zoneRows['zone'] as $row) {
            $this->loadZone($manager, $row);
        }

        $manager->flush();
    }

    private function loadZone(ObjectManager $manager, array $row): void
    {
        $zone = new Zone();
        $zone->setWkt($row['wkt']);
        $zone->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));
        $manager->persist($zone);
    }

    public function getOrder(): int
    {
        return 18;
    }
}
