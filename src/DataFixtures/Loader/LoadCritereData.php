<?php

namespace App\DataFixtures\Loader;

use App\Entity\Critere;
use App\Repository\SituationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadCritereData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private SituationRepository $situationRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $criteresRows = Yaml::parseFile(__DIR__.'/../Files/Critere.yml');
        foreach ($criteresRows['criteres'] as $row) {
            $this->loadCritere($manager, $row);
        }
        $manager->flush();
    }

    public function loadCritere(ObjectManager $manager, array $row): void
    {
        $critere = (new Critere())
            ->setSituation($this->situationRepository->findOneBy(['label' => $row['situation']]))
            ->setLabel($row['label'])
            ->setDescription($row['description'])
            ->setIsArchive($row['is_archive'])
            ->setIsDanger($row['is_danger'])
            ->setCoef($row['coef']);

        $manager->persist($critere);
    }

    public function getOrder(): int
    {
        return 5;
    }
}
