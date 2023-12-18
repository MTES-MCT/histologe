<?php

namespace App\DataFixtures\Loader;

use App\Entity\DesordrePrecision;
use App\Entity\Enum\Qualification;
use App\Repository\DesordreCritereRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadDesordrePrecisionData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private DesordreCritereRepository $desordreCritereRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $desordrePrecisionRows = Yaml::parseFile(__DIR__.'/../Files/DesordrePrecision.yml');
        foreach ($desordrePrecisionRows['desordre_precision'] as $row) {
            $this->loadSituations($manager, $row);
        }

        $manager->flush();
    }

    private function loadSituations(ObjectManager $manager, array $row)
    {
        $desordrePrecision = (new DesordrePrecision())
            ->setCoef($row['coef'])
            ->setIsDanger($row['is_danger'])
            ->setIsSuroccupation($row['is_suroccupation'])
            ->setLabel($row['label'])
            ->setDesordrePrecisionSlug($row['desordre_precision_slug'])
            ->setDesordreCritere($this->desordreCritereRepository->findOneBy(['slugCritere' => $row['desordre_critere_slug']]));

        if (isset($row['qualification'])) {
            $qualifications = [];
            foreach ($row['qualification'] as $qualificationLabel) {
                $qualification = Qualification::tryFrom($qualificationLabel);
                if (null !== $qualification) {
                    $qualifications[] = $qualification;
                }
                $desordrePrecision->setQualification($qualifications);
            }
        }

        $manager->persist($desordrePrecision);
    }

    public function getOrder(): int
    {
        return 10;
    }
}
