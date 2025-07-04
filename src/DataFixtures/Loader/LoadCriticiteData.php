<?php

namespace App\DataFixtures\Loader;

use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Repository\CritereRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadCriticiteData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private CritereRepository $critereRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $criticiteRows = Yaml::parseFile(__DIR__.'/../Files/Criticite.yml');
        foreach ($criticiteRows['criticites'] as $row) {
            $this->loadCriticite($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function loadCriticite(ObjectManager $manager, array $row): void
    {
        $criticite = (new Criticite())
            ->setCritere($this->critereRepository->findOneBy(['label' => $row['critere']]))
            ->setLabel($row['label'])
            ->setIsArchive($row['is_archive'])
            ->setIsDefault($row['is_default'])
            ->setCreatedAt(new \DateTimeImmutable())
            ->setScore($row['score'])
            ->setNewScore($row['new_score'])
            ->setIsDanger($row['is_danger']);

        if (isset($row['qualifications'])) {
            $qualification = [];
            foreach ($row['qualifications'] as $qualificationLabel) {
                $qualification[] = Qualification::from($qualificationLabel);
                $criticite
                ->setQualification($qualification)
                ->setModifiedAt(new \DateTimeImmutable());
            }
        }

        $manager->persist($criticite);
    }

    public function getOrder(): int
    {
        return 5;
    }
}
