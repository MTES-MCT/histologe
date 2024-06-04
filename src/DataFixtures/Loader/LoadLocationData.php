<?php

namespace App\DataFixtures\Loader;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadLocationData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $locationRows = Yaml::parseFile(__DIR__.'/../Files/Location.yml');
        foreach ($locationRows['location'] as $row) {
            $this->loadLocation($manager, $row);
        }

        $manager->flush();
    }

    private function loadLocation(ObjectManager $manager, array $row): void
    {
        $zone = new Location();
        $zone->setLat($row['lat']);
        $zone->setLng($row['lng']);
        $manager->persist($zone);
    }

    public function getOrder(): int
    {
        return 18;
    }
}
