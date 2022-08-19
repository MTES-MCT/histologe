<?php

namespace App\DataFixtures\Loader;

use App\Entity\Situation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSituationData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $situationRows = Yaml::parseFile(__DIR__.'/../Files/Situation.yml');
        foreach ($situationRows['situations'] as $row) {
            $this->loadSituations($manager, $row);
        }

        $manager->flush();
    }

    private function loadSituations(ObjectManager $manager, array $row)
    {
        $situation = (new Situation())
            ->setLabel($row['label'])
            ->setMenuLabel($row['menu_label'])
            ->setIsActive($row['is_active'])
            ->setIsArchive($row['is_archive'])
            ->setCreatedAt(new \DateTimeImmutable())
            ->setModifiedAt(new \DateTimeImmutable());

        $manager->persist($situation);
    }

    public function getOrder(): int
    {
        return 3;
    }
}
