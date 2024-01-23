<?php

namespace App\Factory;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\Qualification\QualificationStatusService;
use DateTimeImmutable;

class SignalementQualificationFactory
{
    public function __construct(
        private QualificationStatusService $qualificationStatusService
    ) {
    }

    public function createInstanceFrom(
        Qualification $qualification,
        QualificationStatus $qualificationStatus,
        ?array $listCriticiteIds = null,
        ?array $listDesordrePrecisionsIds = null,
        bool $isPostVisite = false
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

    public function createNDEInstanceFrom(
        Signalement $signalement,
        array $listNDECriticites = [],
        ?string $dataDateBail = '',
        ?string $dataConsoSizeYear = '',
        ?string $dataConsoYear = '',
        ?string $dataConsoSize = '',
        ?string $dataHasDPE = '',
        ?string $dataDateDPE = '',
    ): SignalementQualification {
        $signalementQualification = new SignalementQualification();
        $signalementQualification->setQualification(Qualification::NON_DECENCE_ENERGETIQUE);

        $dataHasDPEToSave = null;
        $dataConsoToSave = null;
        $dataDateBailToSave = null;
        if ('Je ne sais pas' !== $dataDateBail) {
            if (null !== $signalement->getDateEntree() && $signalement->getDateEntree()->format('Y') >= 2023) {
                $signalementQualification->setDernierBailAt($signalement->getDateEntree());
            } elseif (!empty($dataDateBail)) {
                $signalementQualification->setDernierBailAt(new DateTimeImmutable($dataDateBail));
            }
            $dataDateBailToSave = $signalementQualification->getDernierBailAt()?->format('Y-m-d');
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
        }
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: $signalement->getDateEntree() ? $signalement->getDateEntree()->format('Y-m-d') : null,
            dateDernierBail: $dataDateBailToSave,
            dateDernierDPE: isset($dataDateDPE) ? $dataDateDPE : null,
            superficie: !empty($dataConsoSize) ? $dataConsoSize : null,
            consommationEnergie: $dataConsoToSave,
            dpe: $dataHasDPEToSave
        );
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalementQualification->setStatus(
            $this->qualificationStatusService->getNDEStatus($signalementQualification)
        );

        if (null == $signalement->getCreatedFrom()) {
            $signalementQualification->setCriticites($listNDECriticites);
        } else {
            $signalementQualification->setDesordrePrecisionIds($listNDECriticites);
        }

        return $signalementQualification;
    }
}
