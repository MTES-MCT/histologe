<?php

namespace App\Service\Signalement;

use App\Entity\Enum\QualificationStatus;
use App\Entity\SignalementQualification;

class QualificationStatusService
{
    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023'
        && $signalementQualification->getDetails()['consommation_energie'] > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023'
        && $signalementQualification->getDetails()['consommation_energie'] <= 450) {
            return QualificationStatus::NDE_OK;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023') {
            return QualificationStatus::NDE_CHECK;
        }

        // si avant 2023, on archive la qualification
        return QualificationStatus::ARCHIVED;
    }

    public function getList(): array
    {
        $labelList = QualificationStatus::getLabelList();

        return [
            QualificationStatus::NDE_AVEREE->name => $labelList[QualificationStatus::NDE_AVEREE->name],
            QualificationStatus::NDE_CHECK->name => $labelList[QualificationStatus::NDE_CHECK->name],
            QualificationStatus::NDE_OK->name => $labelList[QualificationStatus::NDE_OK->name],
        ];
    }
}
