<?php

namespace App\DataFixtures\Loader;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadAutoAffectationRuleData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private PartnerRepository $partnerRepository,
        private TerritoryRepository $territoryRepository,
        private UserRepository $userRepository
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $autoAffectationRules = Yaml::parseFile(__DIR__.'/../Files/AutoAffectationRule.yml');
        foreach ($autoAffectationRules['auto_affectation_rules'] as $row) {
            $this->loadAutoAffectationRule($manager, $row);
        }
        $manager->flush();
    }

    public function loadAutoAffectationRule(ObjectManager $manager, array $row): void
    {
        $affectation = (new AutoAffectationRule())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setStatus($row['status'])
            ->setProfileDeclarant($row['profile_declarant'])
            ->setPartnerType(PartnerType::tryFrom($row['partner_type']))
            ->setInseeToInclude($row['insee_to_include'])
            ->setInseeToExclude($row['insee_to_exclude'])
            ->setParc($row['parc'])
            ->setAllocataire($row['allocataire'])
        ;

        $manager->persist($affectation);
    }

    public function getOrder(): int
    {
        return 18;
    }
}
