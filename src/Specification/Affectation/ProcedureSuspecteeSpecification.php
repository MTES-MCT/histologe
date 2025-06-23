<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\Qualification;
use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class ProcedureSuspecteeSpecification implements SpecificationInterface
{
    /**
     * @param ?array<Qualification> $proceduresSuspectees
     */
    public function __construct(private ?array $proceduresSuspectees)
    {
        $this->proceduresSuspectees = $proceduresSuspectees;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        if (!empty($this->proceduresSuspectees)) {
            $signalementQualifications = $signalement->getSignalementQualifications();
            foreach ($signalementQualifications as $signalementQualification) {
                if (QualificationStatus::ARCHIVED !== $signalementQualification->getStatus()
                && \in_array($signalementQualification->getQualification(), $this->proceduresSuspectees)
                ) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
