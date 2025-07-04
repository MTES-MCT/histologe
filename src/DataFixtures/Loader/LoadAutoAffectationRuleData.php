<?php

namespace App\DataFixtures\Loader;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadAutoAffectationRuleData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
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

    /**
     * @param array<string, mixed> $row
     */
    public function loadAutoAffectationRule(ObjectManager $manager, array $row): void
    {
        $affectation = (new AutoAffectationRule())
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setStatus($row['status'])
            ->setProfileDeclarant($row['profile_declarant'])
            ->setPartnerType(PartnerType::tryFrom($row['partner_type']))
            ->setInseeToInclude($row['insee_to_include'])
            ->setInseeToExclude($row['insee_to_exclude'])
            ->setPartnerToExclude($row['partner_to_exclude'] ?? [])
            ->setParc($row['parc'])
            ->setAllocataire($row['allocataire'])
        ;

        if (isset($row['procedures_suspectees'])) {
            $proceduresSuspectees = [];
            if (\is_array($row['procedures_suspectees'])) {
                foreach ($row['procedures_suspectees'] as $procedureSuspectee) {
                    $proceduresSuspectees[] = Qualification::tryFrom($procedureSuspectee);
                }
            } else {
                $proceduresSuspectees[] = Qualification::tryFrom($row['procedures_suspectees']);
            }
            $affectation->setProceduresSuspectees($proceduresSuspectees);
        }

        $manager->persist($affectation);
    }

    public function getOrder(): int
    {
        return 21;
    }
}
