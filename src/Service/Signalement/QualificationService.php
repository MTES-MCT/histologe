<?php

namespace App\Service\Signalement;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Factory\SignalementQualificationFactory;

class QualificationService
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
        $addDanger = true;

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
            if (Qualification::DANGER == $qualification->getQualification()) {
                $addDanger = false;
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

        // IF NOT ADDED YET:
        // If score is higher than 10, we add INSALUBRITE with different status depending on score
        // If criticité with qualification INSALUBRITE, we add INSALUBRITE
        if ($addInsalubrite) {
            $statusInsalubrite = null;
            $listCriticiteInsalubrite = [];
            if ($newScoreCreation >= 10) {
                $statusInsalubrite = $newScoreCreation >= 30 ? QualificationStatus::INSALUBRITE_CHECK : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
            }
            foreach ($signalement->getCriticites() as $criticite) {
                if (\in_array(Qualification::INSALUBRITE->value, $criticite->getQualification())) {
                    $statusInsalubrite = QualificationStatus::INSALUBRITE_CHECK;
                    $listCriticiteInsalubrite[] = $criticite->getId();
                }
            }
            if (!empty($statusInsalubrite)) {
                $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, $statusInsalubrite, $listCriticiteInsalubrite);
                $signalement->addSignalementQualification($signalementQualification);
            }
        }

        // IF NOT ADDED YET:
        // If criticité is DANGER, we add DANGER
        if ($addDanger) {
            $listCriticiteDanger = [];
            foreach ($signalement->getCriticites() as $criticite) {
                if ($criticite->getIsDanger()) {
                    $listCriticiteDanger[] = $criticite->getId();
                }
            }
            if (!empty($listCriticiteDanger)) {
                $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::DANGER, QualificationStatus::DANGER_CHECK, $listCriticiteDanger);
                $signalement->addSignalementQualification($signalementQualification);
            }
        }

        // TODO : remove qualifications when score / criticités are updated
    }
}
