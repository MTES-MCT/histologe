<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Factory\InterventionFactory;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use App\Repository\InterventionRepository;
use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\DossierResponseInterface;
use App\Service\Esabora\Response\Model\DossierArreteSISH;
use App\Service\Esabora\Response\Model\DossierVisiteSISH;

class EsaboraManager
{
    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly SuiviManager $suiviManager,
        private readonly InterventionRepository $interventionRepository,
        private readonly InterventionFactory $interventionFactory,
    ) {
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
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_WAIT);
                    $description = 'remis en attente via Esabora';
                }
                break;
            case EsaboraStatus::ESABORA_ACCEPTED->value:
                if ($this->shouldBeAcceptedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_ACCEPTED);
                    $description = 'accepté via Esabora';
                }

                if ($this->shouldBeClosedViaEsabora($esaboraDossierStatus, $currentStatus)) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_CLOSED);
                    $description = 'cloturé via Esabora';
                }
                break;
            case EsaboraStatus::ESABORA_REFUSED->value:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $this->affectationManager->updateAffectation($affectation, $user, Affectation::STATUS_REFUSED);
                    $description = 'refusé via Esabora';
                }
                break;
        }

        return $description;
    }

    public function createOrUpdateVisite(Affectation $affectation, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierVisiteSISH->getVisiteId()]);
        if (null !== $intervention) {
            $this->updateFromDossierVisite($intervention, $dossierVisiteSISH);
        } else {
            $newIntervention = $this->interventionFactory->createInstanceFrom(
                affectation: $affectation,
                type: InterventionType::fromLabel($dossierVisiteSISH->getVisiteType()),
                scheduledAt: DateParser::parse($dossierVisiteSISH->getVisiteDate()),
                registeredAt: DateParser::parse($dossierVisiteSISH->getVisiteDateEnreg()),
                status: EsaboraStatus::ESABORA_IN_PROGRESS->value === $dossierVisiteSISH->getVisiteEtat()
                    ? Intervention::STATUS_PLANNED
                    : Intervention::STATUS_DONE,
                providerName: InterfacageType::ESABORA->value,
                providerId: $dossierVisiteSISH->getVisiteId(),
                doneBy: $dossierVisiteSISH->getVisitePar(),
                details: $dossierVisiteSISH->getVisiteObservations()
            );

            $this->interventionRepository->save($newIntervention, true);
        }
    }

    public function createOrUpdateArrete(Affectation $affectation, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention = $this->interventionRepository->findOneBy(['providerId' => $dossierArreteSISH->getArreteId()]);
        if (null !== $intervention) {
            $this->updateFromDossierArrete($intervention, $dossierArreteSISH);
        } else {
            $intervention = $this->interventionFactory->createInstanceFrom(
                affectation: $affectation,
                type: InterventionType::ARRETE_PREFECTORAL,
                scheduledAt: DateParser::parse($dossierArreteSISH->getArreteDatePresc()),
                registeredAt: DateParser::parse($dossierArreteSISH->getArreteDate()),
                status: EsaboraStatus::ESABORA_IN_PROGRESS->value === $dossierArreteSISH->getArreteEtat()
                    ? Intervention::STATUS_PLANNED
                    : Intervention::STATUS_DONE,
                providerName: InterfacageType::ESABORA->value,
                providerId: $dossierArreteSISH->getArreteId(),
                details: $this->buildDetailArrete($dossierArreteSISH)
            );

            $this->interventionRepository->save($intervention, true);
        }
    }

    private function updateFromDossierVisite(Intervention $intervention, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierVisiteSISH->getVisiteDate()))
            ->setRegisteredAt(DateParser::parse($dossierVisiteSISH->getVisiteDateEnreg()))
            ->setStatus(EsaboraStatus::ESABORA_IN_PROGRESS->value === $dossierVisiteSISH->getVisiteEtat()
                ? Intervention::STATUS_PLANNED
                : Intervention::STATUS_DONE)
            ->setDoneBy($dossierVisiteSISH->getVisitePar())
            ->setDetails($dossierVisiteSISH->getVisiteObservations());

        $this->interventionRepository->save($intervention, true);
    }

    private function updateFromDossierArrete(Intervention $intervention, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention
            ->setScheduledAt(DateParser::parse($dossierArreteSISH->getArreteDatePresc()))
            ->setDetails($this->buildDetailArrete($dossierArreteSISH))
            ->setRegisteredAt(DateParser::parse($dossierArreteSISH->getArreteDate()))
            ->setStatus(EsaboraStatus::ESABORA_IN_PROGRESS->value === $dossierArreteSISH->getArreteEtat()
                ? Intervention::STATUS_PLANNED
                : Intervention::STATUS_DONE)
            ->setDetails($this->buildDetailArrete($dossierArreteSISH));

        $this->interventionRepository->save($intervention, true);
    }

    private function buildDetailArrete(DossierArreteSISH $dossierArreteSISH): string
    {
        return sprintf('Commentaire: %s<br>Type arrêté: %s<br>Statut arrêté: %s',
            $dossierArreteSISH->getArreteCommentaire(),
            $dossierArreteSISH->getArreteType(),
            $dossierArreteSISH->getArreteStatut()
        );
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
