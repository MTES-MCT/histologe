<?php

namespace App\Service\DashboardWidget;

class WidgetCard
{
    public function __construct(
        private ?int $count = null,
        private ?string $link = null
    ) {
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
