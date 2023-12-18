<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadJobEventData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private PartnerRepository $partnerRepository,
        private SignalementRepository $signalementRepository
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $partnersRows = Yaml::parseFile(__DIR__.'/../Files/JobEvent.yml');
        foreach ($partnersRows['job_event'] as $row) {
            $this->loadJobEvent($manager, $row);
        }
        $manager->flush();
    }

    public function loadJobEvent(ObjectManager $manager, array $row): void
    {
        /** @var Partner $partner */
        $partner = $this->partnerRepository->findOneBy(['email' => $row['partner']]);
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);

        $jobEvent = (new JobEvent())
            ->setService($row['service'])
            ->setPartnerId($partner->getId())
            ->setSignalementId($signalement->getId())
            ->setStatus($row['status'])
            ->setCodeStatus($row['code_status'])
            ->setAction($row['action'])
            ->setPartnerType(PartnerType::tryFrom($row['partner_type']));
        $manager->persist($jobEvent);
    }

    public function getOrder(): int
    {
        return 15;
    }
}
