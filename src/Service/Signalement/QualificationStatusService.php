<?php

namespace App\Service\Signalement;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Factory\SignalementQualificationFactory;
use DateTimeImmutable;
use Twig\Extension\RuntimeExtensionInterface;

class QualificationStatusService implements RuntimeExtensionInterface
{
    public function __construct(
        private SignalementQualificationFactory $signalementQualificationFactory
    ) {
    }

    public function updateQualificationFromScore(Signalement $signalement): void
    {
        $addNonDecence = true;
        $addRSD = true;
        $addInsalubrite = true;

        $listQualifications = $signalement->getSignalementQualifications();
        foreach ($listQualifications as $qualification) {
            if (Qualification::NON_DECENCE == $qualification->getQualification()) {
                $addNonDecence = false;
            }
            if (Qualification::RSD == $qualification->getQualification()) {
                $addRSD = false;
            }
            if (Qualification::INSALUBRITE == $qualification->getQualification()) {
                $addInsalubrite = false;
            }
        }

        $newScoreCreation = $signalement->getNewScoreCreation();

        // IF NOT ADDED YET: In all cases, we add NON_DECENCE and RSD
        if ($addNonDecence) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::NON_DECENCE, QualificationStatus::NON_DECENCE_CHECK);
            $signalement->addSignalementQualification($signalementQualification);
        }
        if ($addRSD) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::RSD, QualificationStatus::RSD_CHECK);
            $signalement->addSignalementQualification($signalementQualification);
        }

        // IF NOT ADDED YET: If score is higher than 10, we add INSALUBRITE with different status depending on score
        if ($addInsalubrite && $newScoreCreation >= 10) {
            $qualificationStatus = $newScoreCreation >= 30 ? QualificationStatus::INSALUBRITE_CHECK : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, $qualificationStatus);
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    public function getNDEStatus(SignalementQualification $signalementQualification): ?QualificationStatus
    {
        // pas de date de bail -> à vérifier
        if (null === $signalementQualification->getDernierBailAt()) {
            return QualificationStatus::NDE_CHECK;
        }

        // bail avant 2023 -> pas concerné
        if ($signalementQualification->getDernierBailAt()->format('Y') < '2023') {
            return QualificationStatus::ARCHIVED;
        }

        // bail après 2023, on passe aux vérifications DPE

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
            $dataDateDPEFormatted = new DateTimeImmutable($signalementQualification->getDetails()['date_dernier_dpe']);
            if ($dataDateDPEFormatted->format('Y') < '2023'
            && null !== $consoEnergie
            && null !== $signalementQualification->getSignalement()?->getSuperficie()
            && $signalementQualification->getSignalement()?->getSuperficie() > 0) {
                $consoEnergie = $consoEnergie / $signalementQualification->getSignalement()?->getSuperficie();
            }
        }

        // en fonction de la conso, on est soit en NDE, soit OK
        if ($consoEnergie > 450) {
            return QualificationStatus::NDE_AVEREE;
        }

        if ($consoEnergie <= 450) {
            return QualificationStatus::NDE_OK;
        }

        return QualificationStatus::NDE_CHECK;
    }

    public function canSeenNDEQualification(?SignalementQualification $signalementQualification): bool
    {
        if (empty($signalementQualification)) {
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
