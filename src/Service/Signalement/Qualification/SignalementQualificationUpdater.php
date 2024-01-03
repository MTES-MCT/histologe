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
        private readonly SignalementQualificationFactory $signalementQualificationFactory,
        private readonly SignalementManager $signalementManager,
    ) {
    }

    public function updateQualificationFromScore(Signalement $signalement): void
    {
        $addNonDecence = true;
        $addRSD = true;
        $existingQualificationInsalubrite = null;
        $existingQualificationDanger = null;
        $existingQualificationSuroccupation = null;

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
            if (Qualification::SUROCCUPATION == $qualification->getQualification()) {
                $existingQualificationSuroccupation = $qualification;
            }
        }

        if (null === $signalement->getCreatedFrom()) {
            $this->addNonDecenceAndRSDQualification($signalement, $addNonDecence, $addRSD);
            $this->updateInsalubriteQualification($signalement, $existingQualificationInsalubrite);
        } else {
            $this->updateSignalementQualificationFromDesordrePrecisions($signalement);
            $this->updateSuroccupationQualification($signalement, $existingQualificationSuroccupation);
        }
        $this->updateDangerQualification($signalement, $existingQualificationDanger);
    }

    /**
     * IF NOT ADDED YET: In all cases, we add NON_DECENCE and RSD.
     */
    private function addNonDecenceAndRSDQualification(Signalement $signalement, bool $addNonDecence, bool $addRSD): void
    {
        if ($addNonDecence) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                Qualification::NON_DECENCE,
                QualificationStatus::NON_DECENCE_CHECK
            );
            $signalement->addSignalementQualification($signalementQualification);
        }
        if ($addRSD) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                Qualification::RSD,
                QualificationStatus::RSD_CHECK
            );
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    /**
     * If score is higher than 10, we add INSALUBRITE_CHECK or INSALUBRITE_MANQUEMENT_CHECK depending on score
     * If criticité with qualification INSALUBRITE, we add INSALUBRITE_CHECK.
     */
    private function updateInsalubriteQualification(
        Signalement $signalement,
        ?SignalementQualification $existingQualificationInsalubrite
    ): void {
        $score = $signalement->getScore();

        $statusInsalubrite = null;
        if ($score >= 10) {
            $statusInsalubrite = $score >= 30
                ? QualificationStatus::INSALUBRITE_CHECK
                : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
        }
        $listCriticiteInsalubrite = [];
        foreach ($signalement->getCriticites() as $criticite) {
            if ($criticite->getQualification()
                && \in_array(Qualification::INSALUBRITE->value, $criticite->getQualification())
            ) {
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
                $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                    Qualification::INSALUBRITE,
                    $statusInsalubrite,
                    $listCriticiteInsalubrite
                );
                $signalement->addSignalementQualification($signalementQualification);
            }

        // If not added yet, but should be added
        } elseif (!empty($statusInsalubrite)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                Qualification::INSALUBRITE,
                $statusInsalubrite,
                $listCriticiteInsalubrite
            );
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    /**
     * First remove (or update) old and inappropriate signalement qualification,
     * then apply DesordrePrecision qualifications to Signalement.
     */
    private function updateSignalementQualificationFromDesordrePrecisions(
        Signalement $signalement,
    ): void {
        $existingSignalementQualifications = $signalement->getSignalementQualifications()->toArray();
        $processedQualifications = [];

        // concatenate all desordrePrecision qualifications
        $allDesordrePrecisionsQualifications = [];
        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            $allDesordrePrecisionsQualifications = array_merge(
                $allDesordrePrecisionsQualifications,
                $desordrePrecision->getQualification()
            );
        }

        // remove (or update) old and inappropriate signalement qualification
        foreach ($existingSignalementQualifications as $existingQualification) {
            $qualification = $existingQualification->getQualification();

            if (!$this->isQualificationExistsInDesordrePrecisions($allDesordrePrecisionsQualifications, $qualification)) {
                $signalement->removeSignalementQualification($existingQualification);
            } else {
                $existingQualification->setStatus(
                    $statusQualification = $this->calculateQualificationStatus($signalement, $existingQualification)
                );

                $associatedDesordrePrecisions = $this->getAssociatedDesordrePrecisions($signalement, $qualification);
                $existingQualification->setCriticites($associatedDesordrePrecisions);
                $processedQualifications[] = $qualification;
            }
        }

        // apply DesordrePrecision qualifications to Signalement
        foreach ($allDesordrePrecisionsQualifications as $qualificationValue) {
            $qualification = Qualification::tryFrom($qualificationValue);
            if (!\in_array($qualification, $processedQualifications, true)) {
                $qualificationExists = $this->isQualificationExistsInSignalement(
                    $existingSignalementQualifications,
                    $qualification
                );

                if (!$qualificationExists) {
                    $statusQualification = $this->calculateQualificationStatus($signalement, $qualification);
                    $associatedDesordrePrecisions = $this->getAssociatedDesordrePrecisions($signalement, $qualification);

                    $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                        $qualification,
                        $statusQualification,
                        $associatedDesordrePrecisions
                    );
                    $signalement->addSignalementQualification($signalementQualification);
                    $processedQualifications[] = $qualification;
                }
            }
        }
    }

    private function calculateQualificationStatus(
        Signalement $signalement,
        Qualification $qualification
    ): ?QualificationStatus {
        switch ($qualification) {
            case Qualification::NON_DECENCE:
                $statusQualification = QualificationStatus::NON_DECENCE_CHECK;
                break;
            case Qualification::RSD:
                $statusQualification = QualificationStatus::RSD_CHECK;
                break;
            case Qualification::ASSSURANTIEL:
                $statusQualification = QualificationStatus::ASSURANTIEL_CHECK;
                break;
            case Qualification::MISE_EN_SECURITE_PERIL:
                $statusQualification = QualificationStatus::MISE_EN_SECURITE_PERIL_CHECK;
                break;
            case Qualification::INSALUBRITE:
                $score = $signalement->getScore();
                $statusQualification = $score >= 30
                    ? QualificationStatus::INSALUBRITE_CHECK
                    : QualificationStatus::INSALUBRITE_MANQUEMENT_CHECK;
                break;
            default:
                $statusQualification = null;
                break;
        }

        return $statusQualification;
    }

    private function getAssociatedDesordrePrecisions(Signalement $signalement, Qualification $qualification): array
    {
        $associatedDesordrePrecisions = [];

        foreach ($signalement->getDesordrePrecisions() as $desordrePrecision) {
            if (\in_array($qualification->value, $desordrePrecision->getQualification())) {
                $associatedDesordrePrecisions[] = $desordrePrecision->getId();
            }
        }

        return $associatedDesordrePrecisions;
    }

    private function isQualificationExistsInSignalement(array $qualifications, Qualification $targetQualification): bool
    {
        foreach ($qualifications as $qualification) {
            if ($qualification->getQualification() === $targetQualification) {
                return true;
            }
        }

        return false;
    }

    private function isQualificationExistsInDesordrePrecisions(array $allQualifications, Qualification $qualification): bool
    {
        return \in_array($qualification, $allQualifications);
    }

    /**
     * If one criticité/precision is DANGER, we add DANGER.
     */
    private function updateDangerQualification(
        Signalement $signalement,
        ?SignalementQualification $existingQualificationDanger
    ): void {
        $listCriticiteDanger = $this->getCriticitesDanger($signalement);
        // If already exists
        if ($existingQualificationDanger) {
            // But should be deleted
            if (empty($listCriticiteDanger)) {
                $signalement->removeSignalementQualification($existingQualificationDanger);
            }
        // If not added yet, but should be added
        } elseif (!empty($listCriticiteDanger)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                Qualification::DANGER,
                QualificationStatus::DANGER_CHECK,
                $listCriticiteDanger
            );
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    private function getCriticitesDanger(
        Signalement $signalement,
    ): array {
        $listCriticiteDanger = [];
        if (null === $signalement->getCreatedFrom()) {
            foreach ($signalement->getCriticites() as $criticite) {
                if ($criticite->getIsDanger()) {
                    $listCriticiteDanger[] = $criticite->getId();
                }
            }
        } else {
            foreach ($signalement->getDesordrePrecisions() as $precision) {
                if ($precision->isIsDanger()) {
                    $listCriticiteDanger[] = $precision->getId();
                }
            }
        }

        return $listCriticiteDanger;
    }

    /**
     * If one precision is Suroccupation, we add SUROCCUPATION.
     */
    private function updateSuroccupationQualification(
        Signalement $signalement,
        ?SignalementQualification $existingQualificationSuroccupation
    ): void {
        $listPrecisionsSuroccupation = $this->getPrecisionsSuroccupation($signalement);
        // If already exists
        if ($existingQualificationSuroccupation) {
            // But should be deleted
            if (empty($listPrecisionsSuroccupation)) {
                $signalement->removeSignalementQualification($existingQualificationSuroccupation);
            }
        // If not added yet, but should be added
        } elseif (!empty($listPrecisionsSuroccupation)) {
            $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                Qualification::SUROCCUPATION,
                QualificationStatus::SUROCCUPATION_CHECK,
                $listPrecisionsSuroccupation
            );
            $signalement->addSignalementQualification($signalementQualification);
        }
    }

    private function getPrecisionsSuroccupation(
        Signalement $signalement,
    ): array {
        $listPrecisionsSuroccupation = [];

        foreach ($signalement->getDesordrePrecisions() as $precision) {
            if ($precision->isIsSuroccupation()) {
                $listPrecisionsSuroccupation[] = $precision->getId();
            }
        }

        return $listPrecisionsSuroccupation;
    }

    public function updateQualificationFromVisiteProcedureList(Signalement $signalement, ?array $procedureTypes): void
    {
        if ($procedureTypes) {
            foreach ($procedureTypes as $procedureType) {
                $signalementQualification = null;
                switch ($procedureType->name) {
                    case ProcedureType::NON_DECENCE->name:
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                            qualification: Qualification::NON_DECENCE,
                            qualificationStatus: QualificationStatus::NON_DECENCE_AVEREE,
                            isPostVisite: true
                        );
                        break;
                    case ProcedureType::RSD->name:
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                            qualification: Qualification::RSD,
                            qualificationStatus: QualificationStatus::RSD_AVEREE,
                            isPostVisite: true
                        );
                        break;
                    case ProcedureType::INSALUBRITE->name:
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                            qualification: Qualification::INSALUBRITE,
                            qualificationStatus: QualificationStatus::INSALUBRITE_AVEREE,
                            isPostVisite: true
                        );
                        break;
                    case ProcedureType::MISE_EN_SECURITE_PERIL->name:
                        $signalementQualification = $this->signalementQualificationFactory->createInstanceFrom(
                            qualification: Qualification::MISE_EN_SECURITE_PERIL,
                            qualificationStatus: QualificationStatus::MISE_EN_SECURITE_PERIL_AVEREE,
                            isPostVisite: true
                        );
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
