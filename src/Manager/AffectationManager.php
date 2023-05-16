<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\DossierResponseInterface;
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

    public function updateAffectation(Affectation $affectation, User $user, string $status): Affectation
    {
        $affectation
            ->setStatut($status)
            ->setAnsweredBy($user)
            ->setAnsweredAt(new \DateTimeImmutable());

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

    public function closeAffectation(Affectation $affectation, User $user, MotifCloture $motif): Affectation
    {
        $affectation
            ->setStatut(Affectation::STATUS_CLOSED)
            ->setMotifCloture($motif)
            ->setAnsweredBy($user);

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

    public function synchronizeAffectationFrom(
        DossierResponseInterface $dossierResponse,
        Affectation $affectation
    ): void {
        $user = $affectation->getPartner()->getUsers()->first();
        $signalement = $affectation->getSignalement();

        $description = $this->updateStatusFor($affectation, $user, $dossierResponse);
        if (!empty($description)) {
            $suivi = $this->suiviManager->createSuivi(
                $user,
                $signalement,
                [
                    'domain' => 'esabora',
                    'action' => 'synchronize',
                    'description' => $description,
                    'name_partner' => $affectation->getPartner()->getNom(),
                ],
            );
            $this->suiviManager->save($suivi);
        }
    }

    public function updateStatusFor(
        Affectation $affectation,
        User $user,
        DossierResponseInterface $dossierResponse
    ): string {
        $description = '';
        $currentStatus = $affectation->getStatut();

        $esaboraStatus = $dossierResponse->getSasEtat();
        $esaboraDossierStatus = $dossierResponse->getEtat();

        switch ($esaboraStatus) {
            case EsaboraStatus::ESABORA_WAIT->value:
                if (Affectation::STATUS_WAIT !== $currentStatus) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_WAIT);
                    $description = 'remis en attente via Esabora';
                }
                break;
            case EsaboraStatus::ESABORA_ACCEPTED->value:
                if ($this->shouldBeAcceptedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_ACCEPTED);
                    $description = 'accepté via Esabora';
                }

                if ($this->shouldBeClosedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_CLOSED);
                    $description = 'cloturé via Esabora';
                }
                break;
            case EsaboraStatus::ESABORA_REFUSED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = 'refusé via Esabora';
                }
                break;
        }

        return $description;
    }

    private function shouldBeAcceptedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_IN_PROGRESS->value === $esaboraDossierStatus
            && Affectation::STATUS_ACCEPTED !== $currentStatus;
    }

    private function shouldBeClosedViaEsabora(string $esaboraDossierStatus, int $currentStatus): bool
    {
        return EsaboraStatus::ESABORA_CLOSED->value === $esaboraDossierStatus
            && Affectation::STATUS_CLOSED !== $currentStatus;
    }
}
