<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ZoneType;
use App\Entity\Zone;
use App\Manager\ZoneManager;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadZoneData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
        private UserRepository $userRepository,
        private PartnerRepository $partnerRepository,
        private ZoneManager $zoneManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $zoneRows = Yaml::parseFile(__DIR__.'/../Files/Zone.yml');
        foreach ($zoneRows['zone'] as $row) {
            $this->loadZone($row);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function loadZone(array $row): void
    {
        $zone = new Zone();
        $wktArea = $row['area'];

        $zone->setName($row['name'])
            ->setType(ZoneType::AUTRE)
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]));

        // Persist zone with WKT area using ZoneManager (handles GEOMETRY conversion)
        $zone = $this->zoneManager->persistZone($zone, $wktArea);

        // Add partners after zone is persisted
        foreach ($row['partners'] as $partner) {
            $zone->addPartner($this->partnerRepository->findOneBy(['email' => $partner]));
        }
        if (isset($row['excluded_partners'])) {
            foreach ($row['excluded_partners'] as $partner) {
                $zone->addExcludedPartner($this->partnerRepository->findOneBy(['email' => $partner]));
            }
        }
    }

    public function getOrder(): int
    {
        return 20;
    }
}
