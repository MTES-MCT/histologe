<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
use Doctrine\Persistence\ManagerRegistry;

class AffectationManager extends Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected SuiviManager $suiviManager,
        string $entityName = Affectation::class
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
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

    public function createAffectationFrom(Signalement $signalement, Partner $partner, User $user): Affectation
    {
        return (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner)
            ->setAffectedBy($user)
            ->setTerritory($partner->getTerritory());
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
                    });
            }
        }
    }

    public function synchronizeAffectationFrom(DossierResponse $dossierResponse, Affectation $affectation): void
    {
        $description = '';
        $isUpdated = false;
        $currentStatus = $affectation->getStatut();
        $user = $affectation->getPartner()->getUsers()->first();
        $signalement = $affectation->getSignalement();
        switch ($dossierResponse->getSasEtat()) {
            case EsaboraService::ESABORA_WAIT:
                if (Affectation::STATUS_ACCEPTED !== $currentStatus) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_WAIT);
                    $description = 'remis en attente via Esabora';
                    $isUpdated = true;
                }
                break;
            case EsaboraService::ESABORA_ACCEPTED:
                if (Affectation::STATUS_ACCEPTED !== $currentStatus) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_ACCEPTED);
                    $description = 'accepté via Esabora';
                    $isUpdated = true;
                }
                break;
            case EsaboraService::ESABORA_REFUSED:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = 'refusé via Esabora';
                    $isUpdated = true;
                }
                break;
        }

        if (EsaboraService::ESABORA_CLOSED === $dossierResponse->getEtat()) {
            if (Affectation::STATUS_CLOSED !== $affectation->getStatut()) {
                $this->updateAffectation($affectation, $user, Affectation::STATUS_CLOSED);
                $isUpdated = true;
            } else {
                $description = 'cloturé via Esabora';
            }
        }

        if ($isUpdated) {
            $suivi = $this->suiviManager->createSuivi(
                $user,
                $signalement, [
                    'domain' => 'esabora',
                    'action' => 'synchronize',
                    'description' => $description,
                    'name_partner' => $affectation->getPartner()->getNom(),
                ],
            );
            $this->suiviManager->save($suivi);
        }
    }
}
