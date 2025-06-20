<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\AffectationAnsweredEvent;
use App\Event\AffectationClosedEvent;
use App\Event\AffectationCreatedEvent;
use App\Messenger\InterconnectionBus;
use App\Messenger\Message\DossierMessageInterface;
use App\Repository\AffectationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
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
        string $entityName = Affectation::class,
    ) {
        parent::__construct($this->managerRegistry, $entityName);
    }

    public function updateAffectation(
        Affectation $affectation,
        User $user,
        int $status,
        ?string $motifRefus = null,
        ?string $message = null,
        ?bool $dispatchAffectationAnsweredEvent = true,
    ): Affectation {
        $affectation
            ->setStatut($status)
            ->setAnsweredBy($user)
            ->setAnsweredAt(new \DateTimeImmutable());

        if (!empty($motifRefus)) {
            $affectation->setMotifRefus(MotifRefus::tryFrom($motifRefus));
        }

        if (Affectation::STATUS_WAIT === $status || Affectation::STATUS_ACCEPTED === $status) {
            $affectation->clearMotifs();
        }

        $this->save($affectation);
        if ($dispatchAffectationAnsweredEvent) {
            $this->dispatchAffectationAnsweredEvent($affectation, $user, $status, $affectation->getMotifRefus(), $message);
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

    public function closeAffectation(
        Affectation $affectation,
        User $user,
        MotifCloture $motif,
        ?string $message = null,
        bool $flush = false): Affectation
    {
        $affectation
            ->setStatut(Affectation::STATUS_CLOSED)
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setMotifCloture($motif)
            ->setAnsweredBy($user);
        if ($flush) {
            $this->save($affectation);
            $this->eventDispatcher->dispatch(
                new AffectationClosedEvent(
                    affectation: $affectation,
                    user: $user,
                    message: $message
                ),
                AffectationClosedEvent::NAME
            );
        }

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
                $this->remove($affectation);
            }
        } else {
            foreach ($partnersIdToRemove as $partnerIdToRemove) {
                $partner = $this->managerRegistry->getRepository(Partner::class)->find($partnerIdToRemove);
                foreach ($signalement->getAffectations() as $affectation) {
                    if ($affectation->getPartner()->getId() === $partner->getId()) {
                        $this->remove($affectation);
                    }
                }
            }
        }
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
    }

    private function dispatchAffectationAnsweredEvent(
        Affectation $affectation,
        User $user,
        int $status,
        ?MotifRefus $motifRefus = null,
        ?string $message = null,
    ): void {
        $this->eventDispatcher->dispatch(
            new AffectationAnsweredEvent($affectation, $user, $status, $motifRefus, $message),
            AffectationAnsweredEvent::NAME
        );
    }
}
