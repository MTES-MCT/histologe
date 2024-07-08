<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\SpecificationInterface;

class CodeInseeSpecification implements SpecificationInterface
{
    private array|string $inseeToInclude;
    private ?array $inseeToExclude;

    public function __construct(array|string $inseeToInclude, ?array $inseeToExclude)
    {
        $this->inseeToInclude = $inseeToInclude;
        $this->inseeToExclude = $inseeToExclude;
    }

    public function isSatisfiedBy(array $params): bool
    {
        if (!isset($params['partner']) || !$params['partner'] instanceof Partner) {
            return false;
        }

        if (!isset($params['signalement']) || !$params['signalement'] instanceof Signalement) {
            return false;
        }
        /** @var Partner $partner */
        $partner = $params['partner'];

        /** @var Signalement $signalement */
        $signalement = $params['signalement'];

        if (!empty($this->inseeToExclude) && \in_array($signalement->getInseeOccupant(), $this->inseeToExclude)) {
            return false;
        }
        if ('all' === $this->inseeToInclude) {
            return true;
        } elseif ('partner_list' === $this->inseeToInclude) {
            if (!empty($partner->getInsee())
                && \in_array($signalement->getInseeOccupant(), $partner->getInsee())) {
                return true;
            }
        } elseif (\is_array($this->inseeToInclude)) {
            if (!empty($this->inseeToInclude)
                && \in_array($signalement->getInseeOccupant(), $this->inseeToInclude)) {
                return true;
            }
        }

        return false;
    }
}
