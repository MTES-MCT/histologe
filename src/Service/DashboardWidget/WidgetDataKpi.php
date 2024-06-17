<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use Symfony\Component\Serializer\Attribute\Groups;

class WidgetDataKpi
{
    public function __construct(
        #[Groups(['widget:read'])]
        private readonly array $widgetCards,
        #[Groups(['widget:read'])]
        private readonly CountSignalement $countSignalement,
        #[Groups(['widget:read'])]
        private readonly CountSuivi $countSuivi,
        #[Groups(['widget:read'])]
        private readonly CountUser $countUser
    ) {
    }

    public function getWidgetCards(): array
    {
        return $this->widgetCards;
    }

    public function getCountSignalement(): CountSignalement
    {
        return $this->countSignalement;
    }

    public function getCountSuivi(): CountSuivi
    {
        return $this->countSuivi;
    }

    public function getCountUser(): CountUser
    {
        return $this->countUser;
    }
}
