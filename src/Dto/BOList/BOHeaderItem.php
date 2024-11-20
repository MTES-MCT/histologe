<?php

namespace App\Dto\BOList;

class BOHeaderItem
{
    public function __construct(
        private readonly ?string $label = null,
        private readonly ?string $scope = null,
        private readonly ?string $class = null,
    ) {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }
}
