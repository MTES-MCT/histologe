<?php

namespace App\Service\Signalement\Qualification;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Factory\SignalementQualificationFactory;

class SignalementQualificationUpdater
{
    public function __construct(
        private SignalementQualificationFactory $signalementQualificationFactory
    ) {
    }

    public function updateQualificationFromScore(Signalement $signalement): void
    {
        $addNonDecence = true;
        $addRSD = true;
        $existingQualificationInsalubrite = null;
        $existingQualificationDanger = null;

        $listQualifications = $signalement->getSignalementQualifications();
        foreach ($listQualifications as $qualification) {
            if (Qualification::NON_DECENCE == $qualification->getQualification()) {
                $addNonDecence = false;
            }
            if (Qualification::RSD == $qualification->getQualification()) {
                $addRSD = false;
            }
            if (Qualification::INSALUBRITE == $qualification->getQualification()) {
                $existingQualificationInsalubrite = $qualification;
            }
            if (Qualification::DANGER == $qualification->getQualification()) {
                $existingQualificationDanger = $qualification;
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

        // INSALUBRITE
        // If score is higher than 10, we add INSALUBRITE_CHECK or INSALUBRITE_MANQUEMENT_CHECK depending on score
        // If criticité with qualification INSALUBRITE, we add INSALUBRITE_CHECK
        $statusInsalubrite = null;
        if ($newScoreCreation >= 10) {
            $statusInsalubrite = $newScoreCreation >= 30 ? QualificationStatus::INSALUBRITE_CHECK : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
        }
        $listCriticiteInsalubrite = [];
        foreach ($signalement->getCriticites() as $criticite) {
            if (\in_array(Qualification::INSALUBRITE->value, $criticite->getQualification())) {
                $statusInsalubrite = QualificationStatus::INSALUBRITE_CHECK;
                $listCriticiteInsalubrite[] = $criticite->getId();
            }
        }
        // If already exists
        if ($existingQualificationInsalubrite) {
            // But should be deleted
            if (empty($statusInsalubrite)) {
                $signalement->removeSignalementQualification($existingQualificationInsalubrite);
            }
        // TODO : but should be changed of status

        // If not added yet, but should be added
        } elseif (!empty($statusInsalubrite)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, $statusInsalubrite, $listCriticiteInsalubrite);
            $signalement->addSignalementQualification($signalementQualification);
        }

        // DANGER
        // If criticité is DANGER, we add DANGER
        $listCriticiteDanger = [];
        foreach ($signalement->getCriticites() as $criticite) {
            if ($criticite->getIsDanger()) {
                $listCriticiteDanger[] = $criticite->getId();
            }
        }
        // If already exists
        if ($existingQualificationDanger) {
            // But should be deleted
            if (empty($listCriticiteDanger)) {
                $signalement->removeSignalementQualification($existingQualificationDanger);
            }
        // If not added yet, but should be added
        } elseif (!empty($listCriticiteDanger)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::DANGER, QualificationStatus::DANGER_CHECK, $listCriticiteDanger);
            $signalement->addSignalementQualification($signalementQualification);
        }
    }
}
