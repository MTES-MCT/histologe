<?php

namespace App\Specification;

class AndSpecification implements SpecificationInterface
{
    private array $specifications;

    public function __construct(SpecificationInterface ...$specifications)
    {
        $this->specifications = $specifications;
    }

    public function isSatisfiedBy(array $params): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($params)) {
                return false;
            }
        }

        return true;
    }
}
