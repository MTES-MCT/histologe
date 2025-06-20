<?php

namespace App\Factory;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\Qualification\QualificationStatusService;

class SignalementQualificationFactory
{
    public function __construct(
        private QualificationStatusService $qualificationStatusService,
    ) {
    }

    /**
     * @param array<int, mixed> $listCriticiteIds
     * @param array<int, mixed> $listDesordrePrecisionsIds
     */
    public function createInstanceFrom(
        Qualification $qualification,
        QualificationStatus $qualificationStatus,
        ?array $listCriticiteIds = null,
        ?array $listDesordrePrecisionsIds = null,
        bool $isPostVisite = false,
    ): SignalementQualification {
        $signalementQualification = new SignalementQualification();
        $signalementQualification->setQualification($qualification);
        $signalementQualification->setStatus($qualificationStatus);
        if (null !== $listCriticiteIds) {
            $signalementQualification->setCriticites($listCriticiteIds);
        }
        if (null !== $listDesordrePrecisionsIds) {
            $signalementQualification->setDesordrePrecisionIds($listDesordrePrecisionsIds);
        }
        $signalementQualification->setIsPostVisite($isPostVisite);

        return $signalementQualification;
    }

    /**
     * @param array<int, mixed> $listNDECriticites
     */
    public function createNDEInstanceFrom(
        Signalement $signalement,
        array $listNDECriticites = [],
        ?string $dataConsoSizeYear = '',
        ?string $dataConsoYear = '',
        ?string $dataConsoSize = '',
        ?string $dataHasDPE = '',
        ?string $dataDateDPE = '',
        ?string $classeEnergetique = '',
    ): SignalementQualification {
        $signalementQualification = new SignalementQualification();
        $signalementQualification->setSignalement($signalement);
        $signalementQualification->setQualification(Qualification::NON_DECENCE_ENERGETIQUE);

        $dataHasDPEToSave = null;
        $dataConsoToSave = null;
        if (
            isset($dataDateDPE)
            && '1970-01-01' === $dataDateDPE
            && !empty($dataConsoYear)
            && !empty($dataConsoSize)
        ) {
            $dataConsoToSave = $dataConsoYear;
        } elseif (!empty($dataConsoSizeYear)) {
            $dataConsoToSave = $dataConsoSizeYear;
        }

        $dataHasDPEToSave = ('' === $dataHasDPE) ? null : $dataHasDPE;
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: $signalement->getDateEntree() ? $signalement->getDateEntree()->format('Y-m-d') : null,
            dateDernierDPE: isset($dataDateDPE) ? $dataDateDPE : null,
            superficie: !empty($dataConsoSize) ? (float) $dataConsoSize : null,
            consommationEnergie: null !== $dataConsoToSave ? (int) $dataConsoToSave : null,
            dpe: null !== $dataHasDPEToSave ? (bool) $dataHasDPEToSave : null,
            classeEnergetique: $classeEnergetique
        );
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalementQualification->setStatus(
            $this->qualificationStatusService->getNDEStatus($signalementQualification)
        );

        if (!$signalement->isV2()) {
            $signalementQualification->setCriticites($listNDECriticites);
        } else {
            $signalementQualification->setDesordrePrecisionIds($listNDECriticites);
        }

        return $signalementQualification;
    }
}
