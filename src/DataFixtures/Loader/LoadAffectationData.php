<?php

namespace App\DataFixtures\Loader;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Event\AffectationCreatedEvent;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;

class LoadAffectationData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UserRepository $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
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

    /**
     * @param array<string, mixed> $row
     */
    public function loadAffectation(ObjectManager $manager, array $row): void
    {
        $affectation = (new Affectation())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setPartner($this->partnerRepository->findOneBy(['email' => $row['partner']]))
            ->setStatut($row['statut'])
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAffectedBy($this->userRepository->findOneBy(['email' => $row['affected_by']]))
            ->setAnsweredBy($this->userRepository->findOneBy(['email' => $row['answered_by']]))
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setIsSynchronized($row['is_synchronized'] ?? false)
        ;

        if (Affectation::STATUS_CLOSED === $row['statut'] && '' !== $row['motif_cloture']) {
            $affectation
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']));
        }

        if (Affectation::STATUS_REFUSED === $row['statut'] && '' !== $row['motif_refus']) {
            $affectation
                ->setMotifRefus(MotifRefus::tryFrom($row['motif_refus']));
        }

        if (isset($row['created_at'])) {
            $affectation
                ->setCreatedAt($createdAt = (new \DateTimeImmutable())->modify($row['created_at']))
                ->setAnsweredAt($createdAt);
        }

        $manager->persist($affectation);
        $this->eventDispatcher->dispatch(new AffectationCreatedEvent($affectation), AffectationCreatedEvent::NAME);
    }

    public function getOrder(): int
    {
        return 15;
    }
}
