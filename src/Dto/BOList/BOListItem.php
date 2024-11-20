<?php

namespace App\Dto\BOList;

class BOListItem
{
    public function __construct(
        private readonly ?string $class = null,
        private readonly ?string $label = null,
        private readonly ?array $badgeLabels = null,
        private readonly ?array $links = null,
    ) {
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getBadgeLabels(): ?array
    {
        return $this->badgeLabels;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }
}
