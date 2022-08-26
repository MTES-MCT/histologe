<?php

namespace App\DataFixtures\Loader;

use App\Entity\Tag;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadTagData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $tagRows = Yaml::parseFile(__DIR__.'/../Files/Tag.yml');
        foreach ($tagRows['tags'] as $row) {
            $this->loadTags($manager, $row);
        }
        $manager->flush();
    }

    private function loadTags(ObjectManager $manager, array $row): void
    {
        $tag = (new Tag())
            ->setLabel($row['label'])
            ->setIsArchive($row['is_archive'])
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));

        $manager->persist($tag);
    }

    public function getOrder(): int
    {
        return 4;
    }
}
