<?php

namespace App\Specification\Affectation;

use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class CodeInseeSpecification implements SpecificationInterface
{
    /**
     * @param string|array<string> $inseeToInclude
     * @param ?array<string>       $inseeToExclude
     */
    public function __construct(private string|array $inseeToInclude, private ?array $inseeToExclude)
    {
        if ('' !== $inseeToInclude) {
            $this->inseeToInclude = explode(',', $inseeToInclude);
        } else {
            $this->inseeToInclude = $inseeToInclude;
        }
        $this->inseeToExclude = $inseeToExclude;
    }

    private function isExcludedSignalement(Signalement $signalement): bool
    {
        $insee = $signalement->getInseeOccupant();

        return null === $insee || '' === $insee || (!empty($this->inseeToExclude) && \in_array($insee, $this->inseeToExclude));
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        if ($this->isExcludedSignalement($signalement)) {
            return false;
        }

        return match ($this->inseeToInclude) {
            '' => true,
            default => $this->isInseeIncluded($signalement->getInseeOccupant()),
        };
    }

    private function isInseeIncluded(string $insee): bool
    {
        return !empty($this->inseeToInclude) && \in_array($insee, $this->inseeToInclude);
    }
}
