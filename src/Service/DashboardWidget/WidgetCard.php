<?php

namespace App\Service\DashboardWidget;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class WidgetCard
{
    public function __construct(
        #[Groups(['widget:read'])]
        private readonly ?string $label = null,
        #[Groups(['widget:read'])]
        private readonly ?int $count = null,
        #[Groups(['widget:read'])]
        private readonly ?string $link = null,
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
