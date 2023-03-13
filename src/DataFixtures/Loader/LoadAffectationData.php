<?php

namespace App\DataFixtures\Loader;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadAffectationData extends Fixture implements OrderedFixtureInterface
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
        $affectationRows = Yaml::parseFile(__DIR__.'/../Files/Affectation.yml');
        foreach ($affectationRows['affectations'] as $row) {
            $this->loadAffectation($manager, $row);
        }
        $manager->flush();
    }

    public function loadAffectation(ObjectManager $manager, array $row): void
    {
        $affectation = (new Affectation())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setPartner($this->partnerRepository->findOneBy(['email' => $row['partner']]))
            ->setStatut($row['statut'])
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAffectedBy($this->userRepository->findOneBy(['email' => $row['affected_by']]))
            ->setAnsweredBy($this->userRepository->findOneBy(['email' => $row['affected_by']]))
            ->setAnsweredAt(new \DateTimeImmutable())
        ;

        if (Affectation::STATUS_CLOSED === $row['statut'] && '' !== $row['motif_cloture']) {
            $affectation
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']));
        }

        $manager->persist($affectation);
    }

    public function getOrder(): int
    {
        return 9;
    }
}
