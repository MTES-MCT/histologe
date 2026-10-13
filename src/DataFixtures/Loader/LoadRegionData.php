<?php

namespace App\DataFixtures\Loader;

use App\Entity\Region;
use App\Service\DepartementRegionHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadRegionData extends Fixture implements OrderedFixtureInterface
{
    public const string REFERENCE_PREFIX = 'region-';

    public function load(ObjectManager $manager): void
    {
        foreach (DepartementRegionHelper::getRegions() as $regionName) {
            $region = (new Region())->setName($regionName);
            $manager->persist($region);
            $this->addReference(self::REFERENCE_PREFIX.$regionName, $region);
        }
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 0;
    }
}
