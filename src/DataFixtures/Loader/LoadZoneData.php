<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ZoneType;
use App\Entity\Zone;
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
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $zoneRows = Yaml::parseFile(__DIR__.'/../Files/Zone.yml');
        foreach ($zoneRows['zone'] as $row) {
            $this->loadZone($manager, $row);
        }

        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function loadZone(ObjectManager $manager, array $row): void
    {
        $zone = new Zone();

        $zone->setArea($row['area'])
            ->setName($row['name'])
            ->setType(ZoneType::AUTRE)
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]));

        // Add partners to the zone
        foreach ($row['partners'] as $partnerEmail) {
            $partnerEntity = $this->partnerRepository->findOneBy(['email' => $partnerEmail]);
            if ($partnerEntity) {
                $zone->addPartner($partnerEntity);
            }
        }

        if (isset($row['excluded_partners'])) {
            foreach ($row['excluded_partners'] as $partnerEmail) {
                $partnerEntity = $this->partnerRepository->findOneBy(['email' => $partnerEmail]);
                if ($partnerEntity) {
                    $zone->addExcludedPartner($partnerEntity);
                }
            }
        }

        // Persist zone - ZoneGeometryPersistListener automatically handles WKT to GEOMETRY conversion
        $manager->persist($zone);
    }

    public function getOrder(): int
    {
        return 20;
    }
}
