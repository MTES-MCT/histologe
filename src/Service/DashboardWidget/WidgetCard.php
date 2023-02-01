<?php

namespace App\Service\DashboardWidget;

class WidgetCard
{
    public function __construct(
        private ?string $label = null,
        private ?int $count = null,
        private ?string $link = null
    ) {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }
}
