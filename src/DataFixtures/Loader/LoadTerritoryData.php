<?php

namespace App\DataFixtures\Loader;

use App\Entity\Territory;
use App\Repository\ConfigRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Yaml\Yaml;

class LoadTerritoryData extends Fixture implements OrderedFixtureInterface
{
    public const DEPARTEMENTS = [
        '13' => 'BOUCHES-DU-RHONE',
        '01' => 'AIN',
        '06' => 'ALPES-MARITIMES',
        '64' => 'PYRENEES-ATLANTIQUE',
    ];

    public function __construct(private ConfigRepository $configRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $territoryRows = Yaml::parseFile(__DIR__.'/../Files/Territory.yml');
        foreach ($territoryRows['territories'] as $row) {
            $this->loadTerritories($manager, $row);
        }
        $manager->flush();
    }

    public function loadTerritories(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create('fr_FR');
        $territory = (new Territory())
            ->setZip($row['zip'])
            ->setName($row['name'])
            ->setIsActive($row['is_active'])
            ->setBbox(json_decode($row['bbox'], true));

        if (isset($row['config'])) {
            $territory->setConfig(
                $this->configRepository->findOneBy(['nomTerritoire' => self::DEPARTEMENTS[$row['config']]])
            );
        }

        $manager->persist($territory);
    }

    public function getOrder(): int
    {
        return 2;
    }
}
