<?php

namespace App\Service\Signalement\Qualification;

use App\Entity\Enum\QualificationStatus;
use App\Entity\SignalementQualification;
use Twig\Extension\RuntimeExtensionInterface;

class QualificationStatusService implements RuntimeExtensionInterface
{
    private const int LIMIT_CONSO_ENERGIE = 450;

    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        $details = $signalementQualification->getDetails();

        // on ne sait pas si on a un DPE -> à vérifier
        if ('' === $details['DPE']
        || null === $details['DPE']) {
            return QualificationStatus::NDE_CHECK;
        }

        // on n'a pas de DPE -> avérée
        if ('0' === $details['DPE']
        || false === $details['DPE']) {
            return QualificationStatus::NDE_AVEREE;
        }

        // on a une DPE, on calcule la conso d'énergie en fonction de la date du DPE
        $consoEnergie = $details['consommation_energie'];

        if (isset($details['date_dernier_dpe'])) {
            $before2023 = false;
            if ('before2023' === $details['date_dernier_dpe']) {
                $before2023 = true;
            } elseif ('post2023' === $details['date_dernier_dpe']) {
                $before2023 = false;
            } else {
                $dataDateDPEFormatted = new \DateTimeImmutable($details['date_dernier_dpe']);
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

        $classeEnergetique = !empty($details['classe_energetique']) ? $details['classe_energetique'] : null;
        $isClasseG = ('G' === $classeEnergetique);
        if ((isset($consoEnergie) && $consoEnergie <= self::LIMIT_CONSO_ENERGIE && !$isClasseG)
            || (null === $consoEnergie && !$isClasseG && !$signalementQualification->hasDesordres())) {
            return QualificationStatus::NDE_OK;
        }

        return QualificationStatus::NDE_CHECK;
    }

    /**
     * @return array<string>
     */
    public function getList(): array
    {
        return [
            QualificationStatus::NDE_AVEREE->name => QualificationStatus::NDE_AVEREE->label(),
            QualificationStatus::NDE_CHECK->name => QualificationStatus::NDE_CHECK->label(),
            QualificationStatus::NDE_OK->name => QualificationStatus::NDE_OK->label(),
        ];
    }
}
