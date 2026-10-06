<?php

namespace App\Service\Signalement\TabOrientation;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Intervention;
use App\Entity\Signalement;

class TabOrientationItem
{
    public const TYPE_PRE_EVALUATION = 'preEvaluation';
    public const TYPE_CONCLUSION_DE_VISITE = 'conclusionDeVisite';
    public const TYPE_PROCEDURE_A_ENGAGER = 'procedureAEngager';
    public const TYPE_PROCEDURE_RETENUE = 'procedureRetenue';

    private Signalement $signalement;
    private \DateTimeImmutable $date;
    private string $type;
    private ?Intervention $intervention;

    public function __construct(
        Signalement $signalement,
        string $type,
        ?Intervention $intervention = null,
    ) {
        $this->signalement = $signalement;
        $this->type = $type;
        $this->intervention = $intervention;

        switch ($type) {
            case self::TYPE_PRE_EVALUATION:
                $this->date = $this->signalement->getCreatedAt();
                break;
            case self::TYPE_CONCLUSION_DE_VISITE:
                $this->date = $this->intervention->getScheduledAt();
                break;
            case self::TYPE_PROCEDURE_A_ENGAGER:
                $firstSuivi = $signalement->getSuivisWithCategory(SuiviCategory::ADD_OR_EDIT_PROCEDURE_ENGAGEE)->first();
                $this->date = $firstSuivi ? $firstSuivi->getCreatedAt() : new \DateTimeImmutable();
                break;
            case self::TYPE_PROCEDURE_RETENUE:
                $this->date = $this->signalement->getClosedAt();
                break;
        }
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }
}
