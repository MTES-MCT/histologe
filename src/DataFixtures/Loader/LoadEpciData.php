<?php

namespace App\DataFixtures\Loader;

use App\Entity\Epci;
use App\Repository\CommuneRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadEpciData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private CommuneRepository $communeRepository)
    {
    }

    public function load(ObjectManager $manager)
    {
        $partnersRows = Yaml::parseFile(__DIR__.'/../Files/Epci.yml');
        foreach ($partnersRows['epcis'] as $row) {
            $this->loadEpci($manager, $row);
        }
        $manager->flush();
    }

    private function loadEpci(ObjectManager $manager, array $row): void
    {
        $communeList = $this->communeRepository->findAll();
        $communes = [];
        foreach ($communeList as $communeItem) {
            $communes[$communeItem->getNom()][$communeItem->getCodePostal()] = $communeItem;
        }

        $epci = (new Epci())
            ->setNom($row['nom'])
            ->setCode($row['code']);

        foreach ($row['communes'] as $commune) {
            foreach ($commune['codesPostaux'] as $codePostal) {
                $epci->addCommune($communes[$commune['nom']][$codePostal]);
            }
        }

        $manager->persist($epci);
    }

    public function getOrder(): int
    {
        return 17;
    }
}
