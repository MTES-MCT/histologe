<?php

namespace App\DataFixtures\Loader;

use App\Entity\DesordreCategorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadDesordreCategorieData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $desordreCategorieRows = Yaml::parseFile(__DIR__.'/../Files/DesordreCategorie.yml');
        foreach ($desordreCategorieRows['desordre_categorie'] as $row) {
            $this->loadSituations($manager, $row);
        }

        $manager->flush();
    }

    private function loadSituations(ObjectManager $manager, array $row)
    {
        $desordreCategorie = (new DesordreCategorie())
            ->setLabel($row['label']);

        $manager->persist($desordreCategorie);
    }

    public function getOrder(): int
    {
        return 15;
    }
}
