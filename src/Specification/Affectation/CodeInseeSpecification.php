<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class CodeInseeSpecification implements SpecificationInterface
{
    public function __construct(private array|string $inseeToInclude, private ?array $inseeToExclude)
    {
        if ('all' !== $inseeToInclude && 'partner_list' !== $inseeToInclude) {
            $this->inseeToInclude = explode(',', $inseeToInclude);
        } else {
            $this->inseeToInclude = $inseeToInclude;
        }
        $this->inseeToExclude = $inseeToExclude;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        /** @var Partner $partner */
        $partner = $context->getPartner();

        if (null === $signalement->getInseeOccupant()
            || '' === $signalement->getInseeOccupant()
            || !empty($this->inseeToExclude) && \in_array($signalement->getInseeOccupant(), $this->inseeToExclude)) {
            return false;
        }

        $result = false;

        switch ($this->inseeToInclude) {
            case 'all':
                $result = true;
                break;
            case 'partner_list':
                $result = !empty($partner->getInsee())
                    && \in_array($signalement->getInseeOccupant(), $partner->getInsee());
                break;
            default:
                $result = !empty($this->inseeToInclude)
                    && \in_array($signalement->getInseeOccupant(), $this->inseeToInclude);
                break;
        }

        return $result;
    }
}
