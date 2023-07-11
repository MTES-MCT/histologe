<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
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
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $interventionRows = Yaml::parseFile(__DIR__.'/../Files/Intervention.yml');
        foreach ($interventionRows['interventions'] as $row) {
            $this->loadInterventions($manager, $row);
        }

        $manager->flush();
    }

    /**
     * @throws \Exception
     */
    private function loadInterventions(ObjectManager $manager, array $row): void
    {
        $intervention = (new Intervention())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setPartner($this->partnerRepository->findOneBy(['email' => $row['partner']]))
            ->setScheduledAt(isset($row['scheduled_at'])
                ? new \DateTimeImmutable($row['scheduled_at'])
                : (new \DateTimeImmutable())->modify('+1 month'))
            ->setType(InterventionType::VISITE)
            ->setDocuments($row['documents'] ?? [])
            ->setDetails($row['details'] ?? null)
            ->setOccupantPresent($row['occupant_present'] ?? null)
            ->setProprietairePresent($row['proprietaire_present'] ?? null)
            ->setStatus($row['status'] ?? Intervention::STATUS_PLANNED);

        if (isset($row['conclude_procedure'])) {
            $concludeProcedures = [];
            foreach ($row['conclude_procedure'] as $concludeProcedure) {
                $concludeProcedures[] = ProcedureType::tryFrom($concludeProcedure);
            }
            $intervention->setConcludeProcedure($concludeProcedures);
        }

        $manager->persist($intervention);
    }

    public function getOrder(): int
    {
        return 13;
    }
}
