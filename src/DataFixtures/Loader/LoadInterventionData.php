<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadInterventionData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private PartnerRepository $partnerRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $interventionRows = Yaml::parseFile(__DIR__.'/../Files/Intervention.yml');
        foreach ($interventionRows['interventions'] as $row) {
            $this->loadInterventions($manager, $row);
        }

        $manager->flush();
    }

    private function loadInterventions(ObjectManager $manager, array $row)
    {
        $intervention = (new Intervention())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setPartner($this->partnerRepository->findOneBy(['email' => $row['partner']]))
            ->setDate(new \DateTimeImmutable())
            ->setType(InterventionType::VISITE)
            ->setStatus(Intervention::STATUS_PLANNED);

        $manager->persist($intervention);
    }

    public function getOrder(): int
    {
        return 13;
    }
}
