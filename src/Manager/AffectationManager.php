<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AffectationManager extends Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected SuiviManager $suiviManager,
        protected LoggerInterface $logger,
        string $entityName = Affectation::class
    ) {
        parent::__construct($this->managerRegistry, $entityName);
    }

    public function updateAffectation(Affectation $affectation, User $user, string $status, ?string $motifRefus = null): Affectation
    {
        $affectation
            ->setStatut($status)
            ->setAnsweredBy($user)
            ->setAnsweredAt(new \DateTimeImmutable());

        if (!empty($motifRefus)) {
            $affectation->setMotifRefus(MotifRefus::tryFrom($motifRefus));
        }

        $this->save($affectation);

        return $affectation;
    }

    public function createAffectationFrom(Signalement $signalement, Partner $partner, ?User $user): Affectation|bool
    {
        $hasAffectation = $signalement
            ->getAffectations()
            ->exists(
                function (int $key, Affectation $affectation) use ($signalement, $partner) {
                    $this->logger->info(
                        sprintf(
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

        return (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner)
            ->setAffectedBy($user ?? null)
            ->setTerritory($partner->getTerritory());
    }

    public function closeAffectation(Affectation $affectation, User $user, MotifCloture $motif, bool $flush = false): Affectation
    {
        $affectation
            ->setStatut(Affectation::STATUS_CLOSED)
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setMotifCloture($motif)
            ->setAnsweredBy($user);
        if ($flush) {
            $this->save($affectation);
        }

        return $affectation;
    }

    public function removeAffectationsFrom(
        Signalement $signalement,
        array $postedPartner = [],
        array $partnersIdToRemove = []
    ): void {
        if (empty($postedPartner) && empty($partnersIdToRemove)) {
            $signalement->getAffectations()->filter(function (Affectation $affectation) {
                $this->remove($affectation);
            });
        } else {
            foreach ($partnersIdToRemove as $partnerIdToRemove) {
                $partner = $this->managerRegistry->getRepository(Partner::class)->find($partnerIdToRemove);
                $signalement->getAffectations()->filter(
                    function (Affectation $affectation) use ($partner) {
                        if ($affectation->getPartner()->getId() === $partner->getId()) {
                            $this->remove($affectation);
                        }
                    }
                );
            }
        }
    }
}
