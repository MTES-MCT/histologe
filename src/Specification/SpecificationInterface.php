<?php

namespace App\Specification;

use App\Specification\Context\SpecificationContextInterface;

interface SpecificationInterface
{
    public function isSatisfiedBy(SpecificationContextInterface $context): bool;
}
