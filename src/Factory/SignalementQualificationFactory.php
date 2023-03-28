<?php

namespace App\Factory;

use App\Dto\Request\Signalement\QualificationNDERequest;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Service\Signalement\QualificationStatusService;
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
        array $listCriticites = []
    ): SignalementQualification {
        $signalementQualification = new SignalementQualification();
        $signalementQualification->setQualification($qualification);
        $signalementQualification->setStatus($qualificationStatus);
        $signalementQualification->setCriticites($listCriticites);

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
            if ($signalement->getDateEntree()->format('Y') >= 2023) {
                $signalementQualification->setDernierBailAt($signalement->getDateEntree());
            } elseif (!empty($dataDateBail)) {
                $signalementQualification->setDernierBailAt(new DateTimeImmutable($dataDateBail));
            }
            $dataDateBailToSave = $signalementQualification->getDernierBailAt()?->format('Y-m-d');
            if (isset($dataDateDPE) && '1970-01-01' === $dataDateDPE && !empty($dataConsoYear) && !empty($dataConsoSize)) {
                $dataConsoToSave = $dataConsoYear;
            } elseif (!empty($dataConsoSizeYear)) {
                $dataConsoToSave = $dataConsoSizeYear;
            }

            $dataHasDPEToSave = ('' === $dataHasDPE) ? null : $dataHasDPE;
        }
        $qualificationNDERequest = new QualificationNDERequest(
            dateEntree: $signalement->getDateEntree()->format('Y-m-d'),
            dateDernierBail: $dataDateBailToSave,
            dateDernierDPE: isset($dataDateDPE) ? $dataDateDPE : null,
            superficie: !empty($dataConsoSize) ? $dataConsoSize : null,
            consommationEnergie: $dataConsoToSave,
            dpe: $dataHasDPEToSave
        );
        $signalementQualification->setDetails($qualificationNDERequest->getDetails());
        $signalementQualification->setStatus($this->qualificationStatusService->getNDEStatus($signalementQualification));
        $signalementQualification->setCriticites($listNDECriticites);

        return $signalementQualification;
    }
}
