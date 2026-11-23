<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ZoneType;
use App\Entity\Zone;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Geometry\GeometryFactory;
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
        private GeometryFactory $geometryFactory,
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
        $territory = $this->territoryRepository->findOneBy(['name' => $row['territory']]);
        if (!$territory) {
            throw new \UnexpectedValueException(sprintf('Territory with name %s not found', $row['territory']));
        }

        $geometry = $this->geometryFactory->createFromWkt($row['area']);
        $zone->setArea($geometry)
            ->setName($row['name'])
            ->setType(ZoneType::AUTRE)
            ->setTerritory($territory)
            ->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]));
        foreach ($row['partners'] as $partner) {
            $partner = $this->partnerRepository->findOneBy(['email' => $partner]);
            if (!$partner) {
                throw new \UnexpectedValueException(sprintf('Partner with email %s not found', $partner));
            }
            $zone->addPartner($partner);
        }
        if (isset($row['excluded_partners'])) {
            foreach ($row['excluded_partners'] as $partner) {
                $partner = $this->partnerRepository->findOneBy(['email' => $partner]);
                if (!$partner) {
                    throw new \UnexpectedValueException(sprintf('Partner with email %s not found', $partner));
                }
                $zone->addExcludedPartner($partner);
            }
        }
        $manager->persist($zone);
    }

    public function getOrder(): int
    {
        return 20;
    }
}
