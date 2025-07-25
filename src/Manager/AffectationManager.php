<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\File;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Event\AffectationClosedEvent;
use App\Event\AffectationCreatedEvent;
use App\Messenger\InterconnectionBus;
use App\Messenger\Message\DossierMessageInterface;
use App\Repository\AffectationRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AffectationManager extends Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected SuiviManager $suiviManager,
        protected LoggerInterface $logger,
        protected HistoryEntryManager $historyEntryManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InterconnectionBus $interconnectionBus,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard,
        string $entityName = Affectation::class,
    ) {
        parent::__construct($this->managerRegistry, $entityName);
    }

    /**
     * @param array<File>|null $files
     */
    public function updateAffectation(
        Affectation $affectation,
        User $user,
        AffectationStatus $status,
        ?MotifRefus $motifRefus = null,
        ?string $message = null,
        ?array $files = [],
        ?bool $dispatchAffectationAnsweredEvent = true,
    ): Affectation {
        $affectation
            ->setStatut($status)
            ->setAnsweredBy($user)
            ->setAnsweredAt(new \DateTimeImmutable());

        if (!empty($motifRefus)) {
            $affectation->setMotifRefus($motifRefus);
        }

        if (AffectationStatus::WAIT === $status || AffectationStatus::ACCEPTED === $status) {
            $affectation->clearMotifs();
        }

        $this->save($affectation);
        if ($dispatchAffectationAnsweredEvent) {
            $this->eventDispatcher->dispatch(
                new AffectationAnsweredEvent($affectation, $user, $status, $affectation->getMotifRefus(), $message, $files),
                AffectationAnsweredEvent::NAME
            );
        }

        return $affectation;
    }

    public function createAffectationFrom(Signalement $signalement, Partner $partner, ?User $user): Affectation|bool
    {
        $hasAffectation = $signalement
            ->getAffectations()
            ->exists(
                function (int $key, Affectation $affectation) use ($signalement, $partner) {
                    $this->logger->info(
                        \sprintf(
                            'Signalement %s - Partner already affected %s - %s',
                            $signalement->getReference(),
                            $key,
                            $affectation->getPartner()->getNom()
                        )
                    );

                    return $affectation->getPartner() === $partner;
                }
            );

        if ($hasAffectation) {
            return false;
        }

        return $this->createAffectation($signalement, $partner, $user);
    }

    public function createAffectation(
        Signalement $signalement,
        Partner $partner,
        ?User $user = null,
    ): Affectation {
        $affectation = (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner)
            ->setAffectedBy($user ?? null)
            ->setTerritory($signalement->getTerritory());

        $this->eventDispatcher->dispatch(new AffectationCreatedEvent($affectation), AffectationCreatedEvent::NAME);

        $this->persist($affectation);
        $this->interconnectionBus->dispatch($affectation);

        return $affectation;
    }

    /**
     * @param iterable<File> $files
     */
    public function closeAffectation(
        Affectation $affectation,
        User $user,
        MotifCloture $motif,
        ?string $message = null,
        iterable $files = [],
        bool $flush = false): Affectation
    {
        $affectation
            ->setStatut(AffectationStatus::CLOSED)
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setMotifCloture($motif)
            ->setAnsweredBy($user);
        if ($flush) {
            $this->save($affectation);
            $this->eventDispatcher->dispatch(
                new AffectationClosedEvent(affectation: $affectation, user: $user, message: $message, files: $files),
                AffectationClosedEvent::NAME
            );
        }
        $this->userSignalementSubscriptionRepository->deleteForAffectation($affectation);

        return $affectation;
    }

    /**
     * @param array<int, mixed> $postedPartner
     * @param array<int, int>   $partnersIdToRemove
     */
    public function removeAffectationsFrom(
        Signalement $signalement,
        array $postedPartner = [],
        array $partnersIdToRemove = [],
    ): void {
        if (empty($postedPartner) && empty($partnersIdToRemove)) {
            foreach ($signalement->getAffectations() as $affectation) {
                $this->removeAffectationAndSubscriptions($affectation);
            }
        } else {
            foreach ($partnersIdToRemove as $partnerIdToRemove) {
                $partner = $this->managerRegistry->getRepository(Partner::class)->find($partnerIdToRemove);
                foreach ($signalement->getAffectations() as $affectation) {
                    if ($affectation->getPartner()->getId() === $partner->getId()) {
                        $this->removeAffectationAndSubscriptions($affectation);
                    }
                }
            }
        }
    }

    private function removeAffectationAndSubscriptions(Affectation $affectation): void
    {
        if ($this->featureNewDashboard) {
            $subscriptions = $this->userSignalementSubscriptionRepository->findForAffectation(affectation: $affectation, excludeRT: true);
            foreach ($subscriptions as $subscription) {
                $this->remove($subscription);
            }
        }

        $this->remove($affectation);
    }

    public function flagAsSynchronized(DossierMessageInterface $dossierMessage): void
    {
        /** @var ?Affectation $affectation */
        $affectation = $this->getRepository()->findOneBy([
            'partner' => $dossierMessage->getPartnerId(),
            'signalement' => $dossierMessage->getSignalementId(),
        ]);
        if (isset($affectation)) {
            $affectation->setIsSynchronized(true);
            $this->save($affectation);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function deleteAffectationsByPartner(Partner $partner): void
    {
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->getRepository();
        $affectationRepository->deleteAffectationsByPartner($partner);
        $this->userSignalementSubscriptionRepository->deleteForSignalementOrPartner(partner: $partner);
    }

    public function removeAffectationAndSubscription(Affectation $affectation): void
    {
        $this->remove($affectation);
        $this->userSignalementSubscriptionRepository->deleteForAffectation($affectation);
    }
}
