<?php

namespace App\Specification;

interface SpecificationInterface
{
    public function isSatisfiedBy(array $params): bool;
}
