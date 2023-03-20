<?php

namespace App\Service\Signalement;

use App\Entity\Enum\QualificationStatus;
use App\Entity\SignalementQualification;
use DateTimeImmutable;

class QualificationStatusService
{
    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        if (null == $signalementQualification->getDernierBailAt()) {
            return QualificationStatus::NDE_CHECK;
        }
        if ('' == $signalementQualification->getDetails()['DPE']) {
            return QualificationStatus::NDE_CHECK;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023'
        && '0' === $signalementQualification->getDetails()['DPE']) {
            return QualificationStatus::NDE_AVEREE;
        }

        $consoEnergie = $signalementQualification->getDetails()['consommation_energie'];
        if (isset($signalementQualification->getDetails()['date_dernier_dpe'])) {
            $dataDateDPEFormatted = new DateTimeImmutable($signalementQualification->getDetails()['date_dernier_dpe']);
            if ($dataDateDPEFormatted->format('Y') < '2023'
            && null !== $consoEnergie
            && null !== $signalementQualification->getSignalement()?->getSuperficie()) {
                $consoEnergie = $consoEnergie / $signalementQualification->getSignalement()?->getSuperficie();
            }
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023'
        && $consoEnergie > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($signalementQualification->getDernierBailAt()->format('Y') >= '2023'
        && $consoEnergie <= 450) {
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
        return [
            QualificationStatus::NDE_AVEREE->name => QualificationStatus::NDE_AVEREE->label(),
            QualificationStatus::NDE_CHECK->name => QualificationStatus::NDE_CHECK->label(),
            QualificationStatus::NDE_OK->name => QualificationStatus::NDE_OK->label(),
        ];
    }
}
