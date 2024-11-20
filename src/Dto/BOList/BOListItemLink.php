<?php

namespace App\Dto\BOList;

class BOListItemLink
{
    public function __construct(
        private readonly ?string $href = null,
        private readonly ?string $class = null,
        private readonly ?array $attrList = null,
    ) {
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getAttrList(): ?array
    {
        return $this->attrList;
    }
}
