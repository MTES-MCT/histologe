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
            // on enregistre l'object region en mémoire référencée par une clé.
            // On peux ainsi le récupérer via getReference dans les fixtures qui suivent sans avoir a refaire un appel DB.
            // Voir LoadTerritoryData.
            $this->addReference(self::REFERENCE_PREFIX.$regionName, $region);
        }
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 0;
    }
}
