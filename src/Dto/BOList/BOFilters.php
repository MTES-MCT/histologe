<?php

namespace App\Dto\BOList;

class BOFilters
{
    public function __construct(
        private readonly ?array $formKeys = null,
        private readonly ?string $reinitSlug = null,
    ) {
    }

    public function getFormKeys(): ?array
    {
        return $this->formKeys;
    }

    public function getReinitSlug(): ?string
    {
        return $this->reinitSlug;
    }
}
