<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\Serializer\Attribute\Groups;

class WidgetCard
{
    public function __construct(
        #[Groups(['widget:read'])]
        private readonly ?string $label = null,
        #[Groups(['widget:read'])]
        private readonly ?int $count = null,
        #[Groups(['widget:read'])]
        private readonly ?string $link = null
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
