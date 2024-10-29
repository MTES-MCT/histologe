<?php

namespace App\DataFixtures\Loader;

use App\Entity\Territory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadTerritoryData extends Fixture implements OrderedFixtureInterface
{
    public function __construct()
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
        $territory = (new Territory())
            ->setZip($row['zip'])
            ->setName($row['name'])
            ->setIsActive($row['is_active'])
            ->setBbox(json_decode($row['bbox'], true))
            ->setTimezone($row['timezone'] ?? 'Europe/Paris');

        if (isset($row['authorized_codes_insee'])) {
            $territory->setAuthorizedCodesInsee($row['authorized_codes_insee']);
        }
        if (isset($row['is_grille_visite_disabled'])) {
            $territory->setIsGrilleVisiteDisabled($row['is_grille_visite_disabled']);
        }

        $manager->persist($territory);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
