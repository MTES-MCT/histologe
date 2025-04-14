<?php

namespace App\Service\Signalement\Qualification;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\SignalementQualification;
use Twig\Extension\RuntimeExtensionInterface;

class QualificationStatusService implements RuntimeExtensionInterface
{
    private const int LIMIT_CONSO_ENERGIE = 450;

    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        // on ne sait pas si on a un DPE -> à vérifier
        if ('' === $signalementQualification->getDetails()['DPE']
        || null === $signalementQualification->getDetails()['DPE']) {
            return QualificationStatus::NDE_CHECK;
        }

        // on n'a pas de DPE -> avérée
        if ('0' === $signalementQualification->getDetails()['DPE']
        || false === $signalementQualification->getDetails()['DPE']) {
            return QualificationStatus::NDE_AVEREE;
        }

        // on a une DPE, on calcule la conso d'énergie en fonction de la date du DPE
        $consoEnergie = $signalementQualification->getDetails()['consommation_energie'];

        if (isset($signalementQualification->getDetails()['date_dernier_dpe'])) {
            $before2023 = false;
            if ('before2023' === $signalementQualification->getDetails()['date_dernier_dpe']) {
                $before2023 = true;
            } elseif ('post2023' === $signalementQualification->getDetails()['date_dernier_dpe']) {
                $before2023 = false;
            } else {
                $dataDateDPEFormatted = new \DateTimeImmutable($signalementQualification->getDetails()['date_dernier_dpe']);
                if ($dataDateDPEFormatted->format('Y') < '2023') {
                    $before2023 = true;
                }
            }
            if ($before2023
            && null !== $consoEnergie
            && null !== $signalementQualification->getSignalement()->getSuperficie()
            && $signalementQualification->getSignalement()->getSuperficie() > 0) {
                $consoEnergie /= $signalementQualification->getSignalement()->getSuperficie();
            }
        }

        // en fonction de la conso, on est soit en NDE, soit OK
        if (isset($consoEnergie) && $consoEnergie > self::LIMIT_CONSO_ENERGIE) {
            return QualificationStatus::NDE_AVEREE;
        }

        $classeEnergetique = !empty($signalementQualification->getDetails()['classe_energetique']) ? $signalementQualification->getDetails()['classe_energetique'] : null;
        if ((isset($consoEnergie) && $consoEnergie <= self::LIMIT_CONSO_ENERGIE && 'G' !== $classeEnergetique)
            || (null === $consoEnergie && 'G' !== $classeEnergetique && !$signalementQualification->hasDesordres())) {
            return QualificationStatus::NDE_OK;
        }

        return QualificationStatus::NDE_CHECK;
    }

    public function canSeenNDEQualification(?SignalementQualification $signalementQualification): bool
    {
        if (empty($signalementQualification)) {
            return false;
        }

        if (Qualification::NON_DECENCE_ENERGETIQUE !== $signalementQualification->getQualification()) {
            return false;
        }

        return QualificationStatus::NDE_AVEREE == $signalementQualification->getStatus() || QualificationStatus::NDE_CHECK == $signalementQualification->getStatus();
    }

    public function canSeenNDEEditZone(?SignalementQualification $signalementQualification): bool
    {
        if (empty($signalementQualification)) {
            return false;
        }

        return QualificationStatus::NDE_AVEREE == $signalementQualification->getStatus() || QualificationStatus::NDE_CHECK == $signalementQualification->getStatus() || QualificationStatus::NDE_OK == $signalementQualification->getStatus();
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
