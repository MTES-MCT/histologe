<?php

namespace App\DataFixtures\Loader;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Event\AffectationCreatedEvent;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Yaml;

class LoadAffectationData extends Fixture implements OrderedFixtureInterface
{
    private ObjectManager $manager;
    private User $userAdmin;

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UserRepository $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->userAdmin = $this->userRepository->findOneBy(['email' => $this->userSystemEmail]);
        $this->manager = $manager;
        $affectationRows = Yaml::parseFile(__DIR__.'/../Files/Affectation.yml');
        foreach ($affectationRows['affectations'] as $row) {
            $this->loadAffectation($row);
        }
        $this->manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadAffectation(array $row): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
        if (!$signalement) {
            $signalement = $this->signalementRepository->findOneBy(['uuid' => $row['signalement']]);
        }
        $partner = $this->partnerRepository->findOneBy(['email' => $row['partner']]);
        $answeredBy = $this->userRepository->findOneBy(['email' => $row['answered_by']]);
        $affectation = (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner)
            ->setStatut(AffectationStatus::tryFrom($row['statut']))
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAffectedBy($this->userRepository->findOneBy(['email' => $row['affected_by']]))
            ->setAnsweredBy($answeredBy)
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setIsSynchronized($row['is_synchronized'] ?? false)
        ;

        if (AffectationStatus::CLOSED->value === $row['statut'] && '' !== $row['motif_cloture']) {
            $affectation
                ->setMotifCloture(MotifCloture::tryFrom($row['motif_cloture']));
        }

        if (AffectationStatus::REFUSED === $row['statut'] && '' !== $row['motif_refus']) {
            $affectation
                ->setMotifRefus(MotifRefus::tryFrom($row['motif_refus']));
        }

        if (isset($row['created_at'])) {
            $affectation
                ->setCreatedAt($createdAt = (new \DateTimeImmutable())->modify($row['created_at']))
                ->setAnsweredAt($createdAt);
        }

        $this->manager->persist($affectation);
        $this->eventDispatcher->dispatch(new AffectationCreatedEvent($affectation), AffectationCreatedEvent::NAME);

        if (AffectationStatus::ACCEPTED === $affectation->getStatut()) {
            foreach ($partner->getUsers() as $user) {
                if (($user->isUserPartner() || $user->isPartnerAdmin()) && UserStatus::ARCHIVE !== $user->getStatut()) {
                    $subscription = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $user, 'signalement' => $affectation->getSignalement()]);
                    if ($subscription) {
                        continue;
                    }
                    $subscription = new UserSignalementSubscription();
                    $subscription
                        ->setUser($user)
                        ->setSignalement($affectation->getSignalement())
                        ->setCreatedBy($this->userAdmin)
                        ->setIsLegacy(true);
                    $this->manager->persist($subscription);
                }
            }
        }
    }

    public function getOrder(): int
    {
        return 15;
    }
}
