<?php

namespace App\Service\Signalement;

use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;

class SignalementQualificationService
{
    public function __construct(private Signalement $signalement, private SignalementQualification $signalementQualification)
    {
    }

    public function updateNDEStatus(): ?QualificationStatus
    {
        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $this->signalementQualification->getDetails()['consommation_energie'] > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $this->signalementQualification->getDetails()['consommation_energie'] <= 450) {
            return QualificationStatus::NDE_OK;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023') {
            return QualificationStatus::NDE_CHECK;
        }

        return null;
    }
}
