<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
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

    public function createInterventions(
        Affectation $affectation,
        array $dossierCollection,
    ): void {
        foreach ($dossierCollection as $dossier) {
            if ($dossier instanceof DossierVisiteSISH) {
                $this->createVisite($affectation, $dossier);
            } else {
                $this->createArrete($affectation, $dossier);
            }
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

    private function createVisite(Affectation $affectation, DossierVisiteSISH $dossierVisiteSISH): void
    {
        $intervention = $this->interventionFactory->createInstanceFrom(
            affectation: $affectation,
            type: InterventionType::VISITE,
            scheduledAt: $this->parseDate($dossierVisiteSISH->getVisiteDate()),
            registeredAt: $this->parseDate($dossierVisiteSISH->getVisiteDateEnreg()),
            status: EsaboraStatus::ESABORA_IN_PROGRESS === $dossierVisiteSISH->getVisiteEtat()
                ? Intervention::STATUS_PLANNED
                : Intervention::STATUS_DONE,
            providerId: $dossierVisiteSISH->getVisiteNum(),
            doneBy: $dossierVisiteSISH->getVisitePar(),
            details: $dossierVisiteSISH->getVisiteObservations()
        );

        $this->interventionRepository->save($intervention);
    }

    private function createArrete(Affectation $affectation, DossierArreteSISH $dossierArreteSISH): void
    {
        $intervention = $this->interventionFactory->createInstanceFrom(
            affectation: $affectation,
            type: InterventionType::ARRETE_PREFECTORAL,
            scheduledAt: $this->parseDate($dossierArreteSISH->getArreteDatePresc()),
            registeredAt: $this->parseDate($dossierArreteSISH->getArreteDate()),
            status: EsaboraStatus::ESABORA_IN_PROGRESS === $dossierArreteSISH->getArreteEtat()
                ? Intervention::STATUS_PLANNED
                : Intervention::STATUS_DONE,
            providerId: $dossierArreteSISH->getArreteNumero(),
            details: $dossierArreteSISH->getArreteCommentaire()
        );

        $this->interventionRepository->save($intervention, true);
    }

    private function parseDate(string $date): \DateTimeImmutable
    {
        if (false !== $dateParsed = \DateTimeImmutable::createFromFormat(
            AbstractEsaboraService::FORMAT_DATE_TIME,
            $date)
        ) {
            return $dateParsed;
        }

        return \DateTimeImmutable::createFromFormat(AbstractEsaboraService::FORMAT_DATE, $date);
    }
}
