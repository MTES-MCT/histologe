<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Factory\FileFactory;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadInterventionData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly UserRepository $userRepository,
        private readonly FileFactory $fileFactory,
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
            ->setScheduledAt($this->getScheduledAt($row))
            ->setType(InterventionType::VISITE)
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
        if (isset($row['user']) && isset($row['documents'])) {
            $user = $this->userRepository->findOneBy(['email' => $row['user']]);
            foreach ($row['documents'] as $document) {
                $file = $this->fileFactory->createInstanceFrom(
                    filename: $document,
                    title: $document,
                    signalement: $intervention->getSignalement(),
                    user: $user,
                    intervention: $intervention,
                    documentType: DocumentType::PROCEDURE_RAPPORT_DE_VISITE
                );
                $manager->persist($file);
            }
        }

        $manager->persist($intervention);
    }

    public function getScheduledAt(array $row): \DateTimeImmutable
    {
        if (isset($row['scheduled_at'])) {
            if (str_contains($row['scheduled_at'], '2 days')) {
                return (new \DateTimeImmutable())->modify($row['scheduled_at']);
            }

            return new \DateTimeImmutable($row['scheduled_at']);
        }

        return (new \DateTimeImmutable())->modify('+1 month');
    }

    public function getOrder(): int
    {
        return 19;
    }
}
