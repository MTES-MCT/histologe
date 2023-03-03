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
        if (null == $this->signalementQualification->getDernierBailAt()) {
            return QualificationStatus::NDE_CHECK;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023' && '0' === $this->signalementQualification->getDetails()['DPE']) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $this->signalementQualification->getDetails()['consommation_energie'] > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023' && $this->signalementQualification->getDetails()['consommation_energie'] <= 450) {
            return QualificationStatus::NDE_OK;
        }

        if ($this->signalementQualification->getDernierBailAt()->format('Y') >= '2023') {
            return QualificationStatus::NDE_CHECK;
        }

        // si avant 2023, on archive la qualification
        return QualificationStatus::ARCHIVED;
    }
}
