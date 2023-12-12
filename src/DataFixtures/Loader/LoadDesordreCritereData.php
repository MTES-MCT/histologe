<?php

namespace App\DataFixtures\Loader;

use App\Entity\DesordreCritere;
use App\Entity\Enum\DesordreCritereZone;
use App\Repository\DesordreCategorieRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadDesordreCritereData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private DesordreCategorieRepository $desordreCategorieRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $desordreCritereRows = Yaml::parseFile(__DIR__.'/../Files/DesordreCritere.yml');
        foreach ($desordreCritereRows['desordre_critere'] as $row) {
            $this->loadSituations($manager, $row);
        }

        $manager->flush();
    }

    private function loadSituations(ObjectManager $manager, array $row)
    {
        $desordreCritere = (new DesordreCritere())
            ->setSlugCategorie($row['slug_categorie'])
            ->setLabelCategorie($row['label_categorie'])
            ->setZoneCategorie(DesordreCritereZone::from($row['zone_categorie']))
            ->setSlugCritere($row['slug_critere'])
            ->setLabelCritere($row['label_critere'])
            ->setDesordreCategorie($this->desordreCategorieRepository->findOneBy(['label' => $row['desordre_categorie_label']]));

        $manager->persist($desordreCritere);
    }

    public function getOrder(): int
    {
        return 16;
    }
}
