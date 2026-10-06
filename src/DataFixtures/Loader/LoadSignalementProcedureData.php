<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ProcedureCategory;
use App\Entity\Enum\ProcedureType;
use App\Entity\SignalementProcedure;
use App\Repository\SignalementRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSignalementProcedureData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $rows = Yaml::parseFile(__DIR__.'/../Files/SignalementProcedure.yml');
        foreach ($rows['signalement_procedures'] as $row) {
            $this->loadSignalementProcedure($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadSignalementProcedure(ObjectManager $manager, array $row): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);

        $signalementProcedure = (new SignalementProcedure())
            ->setSignalement($signalement)
            ->setProcedureType(ProcedureType::from($row['procedure_type']))
            ->setProcedureCategory(ProcedureCategory::from($row['procedure_category']));

        $manager->persist($signalementProcedure);
    }

    public function getOrder(): int
    {
        return 27;
    }
}
