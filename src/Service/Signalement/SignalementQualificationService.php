<?php

namespace App\Service\Signalement;

use App\Entity\Enum\QualificationStatus;
use App\Entity\SignalementQualification;

class SignalementQualificationService
{
    public function __construct()
    {
    }

    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $signalementQualification->getDetails()['consommation_energie'] > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $signalementQualification->getDetails()['consommation_energie'] <= 450) {
            return QualificationStatus::NDE_OK;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023') {
            return QualificationStatus::NDE_CHECK;
        }

        // si avant 2023, on archive la qualification
        return QualificationStatus::ARCHIVED;
    }
}
