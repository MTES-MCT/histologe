<?php

namespace App\DataFixtures\Loader;

use App\Entity\Bailleur;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadBailleurData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $bailleursRows = Yaml::parseFile(__DIR__.'/../Files/Bailleur.yml');
        foreach ($bailleursRows['bailleurs'] as $row) {
            $this->loadBailleurs($manager, $row);
        }
    }

    public function loadBailleurs(ObjectManager $manager, array $row): void
    {
        /** @var Territory $territory */
        $territory = $this->territoryRepository->findOneBy(['name' => $row['territory']]);
        /** @var Bailleur $bailleur */
        $bailleur = $manager->getRepository(Bailleur::class)->findOneBy(['name' => $row['name']]);
        if (null === $bailleur) {
            $bailleur = (new Bailleur())
                ->setName($row['name'])
                ->addTerritory($territory)
                ->setIsSocial($row['is_social']);
        } else {
            $bailleur->addTerritory($territory);
        }
        $manager->persist($bailleur);
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 18;
    }
}
