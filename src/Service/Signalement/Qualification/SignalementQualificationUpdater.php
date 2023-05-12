<?php

namespace App\Service\Signalement\Qualification;

use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Factory\SignalementQualificationFactory;
use App\Manager\SignalementManager;

class SignalementQualificationUpdater
{
    public function __construct(
        private SignalementQualificationFactory $signalementQualificationFactory,
        private SignalementManager $signalementManager,
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

        $this->addNonDecenceAndRSDQualification($signalement, $addNonDecence, $addRSD);
        $this->updateInsalubriteQualification($signalement, $existingQualificationInsalubrite);
        $this->updateDangerQualification($signalement, $existingQualificationDanger);
    }

    /**
     * IF NOT ADDED YET: In all cases, we add NON_DECENCE and RSD.
     */
    private function addNonDecenceAndRSDQualification(Signalement $signalement, bool $addNonDecence, bool $addRSD)
    {
        if ($addNonDecence) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::NON_DECENCE, QualificationStatus::NON_DECENCE_CHECK);
            $signalement->addSignalementQualification($signalementQualification);
        }
        if ($addRSD) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::RSD, QualificationStatus::RSD_CHECK);
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    /**
     * If score is higher than 10, we add INSALUBRITE_CHECK or INSALUBRITE_MANQUEMENT_CHECK depending on score
     * If criticité with qualification INSALUBRITE, we add INSALUBRITE_CHECK.
     */
    private function updateInsalubriteQualification(Signalement $signalement, ?SignalementQualification $existingQualificationInsalubrite)
    {
        $score = $signalement->getScore();

        $statusInsalubrite = null;
        if ($score >= 10) {
            $statusInsalubrite = $score >= 30 ? QualificationStatus::INSALUBRITE_CHECK : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
        }
        $listCriticiteInsalubrite = [];
        foreach ($signalement->getCriticites() as $criticite) {
            if ($criticite->getQualification() && \in_array(Qualification::INSALUBRITE->value, $criticite->getQualification())) {
                $statusInsalubrite = QualificationStatus::INSALUBRITE_CHECK;
                $listCriticiteInsalubrite[] = $criticite->getId();
            }
        }

        // If already exists
        if ($existingQualificationInsalubrite) {
            // But should be deleted
            if (empty($statusInsalubrite)) {
                $signalement->removeSignalementQualification($existingQualificationInsalubrite);

            // But should be changed of status
            } elseif ($statusInsalubrite !== $existingQualificationInsalubrite->getStatus()) {
                $signalement->removeSignalementQualification($existingQualificationInsalubrite);
                $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, $statusInsalubrite, $listCriticiteInsalubrite);
                $signalement->addSignalementQualification($signalementQualification);
            }

        // If not added yet, but should be added
        } elseif (!empty($statusInsalubrite)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, $statusInsalubrite, $listCriticiteInsalubrite);
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    /**
     * If one criticité is DANGER, we add DANGER.
     */
    private function updateDangerQualification(Signalement $signalement, ?SignalementQualification $existingQualificationDanger)
    {
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

    public function updateQualificationFromVisiteProcedureList(Signalement $signalement, ?array $procedureTypes)
    {
        $existingQualificationNonDecence = null;
        $existingQualificationRSD = null;
        $existingQualificationInsalubrite = null;
        $existingQualificationPeril = null;
        $listQualifications = $signalement->getSignalementQualifications();
        foreach ($listQualifications as $qualification) {
            if (Qualification::NON_DECENCE == $qualification->getQualification()) {
                $existingQualificationNonDecence = $qualification;
            }
            if (Qualification::RSD == $qualification->getQualification()) {
                $existingQualificationRSD = $qualification;
            }
            if (Qualification::INSALUBRITE == $qualification->getQualification()) {
                $existingQualificationInsalubrite = $qualification;
            }
            if (Qualification::MISE_EN_SECURITE_PERIL == $qualification->getQualification()) {
                $existingQualificationPeril = $qualification;
            }
        }

        if ($procedureTypes) {
            foreach ($procedureTypes as $procedureType) {
                $signalementQualification = null;
                switch ($procedureType->name) {
                    case ProcedureType::NON_DECENCE->name:
                        if ($existingQualificationNonDecence) {
                            $signalement->removeSignalementQualification($existingQualificationNonDecence);
                        }
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::NON_DECENCE, QualificationStatus::NON_DECENCE_AVEREE);
                        break;
                    case ProcedureType::RSD->name:
                        if ($existingQualificationRSD) {
                            $signalement->removeSignalementQualification($existingQualificationRSD);
                        }
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::RSD, QualificationStatus::RSD_AVEREE);
                        break;
                    case ProcedureType::INSALUBRITE->name:
                        if ($existingQualificationInsalubrite) {
                            $signalement->removeSignalementQualification($existingQualificationInsalubrite);
                        }
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::INSALUBRITE, QualificationStatus::INSALUBRITE_AVEREE);
                        break;
                    case ProcedureType::MISE_EN_SECURITE_PERIL->name:
                        if ($existingQualificationPeril) {
                            $signalement->removeSignalementQualification($existingQualificationPeril);
                        }
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(Qualification::MISE_EN_SECURITE_PERIL, QualificationStatus::MISE_EN_SECURITE_PERIL_AVEREE);
                        break;
                    default:
                        break;
                }
                if ($signalementQualification) {
                    $signalement->addSignalementQualification($signalementQualification);
                }
            }
            $this->signalementManager->save($signalement);
        }
    }
}
