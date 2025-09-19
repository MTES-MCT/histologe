<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\PartnerType;
use App\Entity\UserApiPermission;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadUserApiPermission extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $UAPRows = Yaml::parseFile(__DIR__.'/../Files/UserApiPermission.yml');
        foreach ($UAPRows['user_api_permissions'] as $row) {
            $this->loadUserApiPermission($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function loadUserApiPermission(ObjectManager $manager, array $row): void
    {
        $user = $this->userRepository->findOneBy(['email' => $row['user']]);
        $userApiPermission = new UserApiPermission();
        $userApiPermission->setUser($user);
        if (isset($row['partner'])) {
            $partner = $this->partnerRepository->findOneBy(['nom' => $row['partner']]);
            $userApiPermission->setPartner($partner);
        } else {
            if (isset($row['territory'])) {
                $territory = $this->territoryRepository->findOneBy(['name' => $row['territory']]);
                $userApiPermission->setTerritory($territory);
            }
            if (isset($row['partner_type'])) {
                $partnerType = PartnerType::from($row['partner_type']);
                $userApiPermission->setPartnerType($partnerType);
            }
        }
        $manager->persist($userApiPermission);
    }

    public function getOrder(): int
    {
        return 9;
    }
}
