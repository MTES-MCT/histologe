<?php

namespace App\Specification;

use App\Specification\Context\SpecificationContextInterface;

class AndSpecification implements SpecificationInterface
{
    private array $specifications;

    public function __construct(SpecificationInterface ...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return false;
            }
        }

        return true;
    }
}
