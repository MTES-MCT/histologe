<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;

class WidgetDataKpi
{
    public function __construct(
        private readonly array $widgetCards,
        private readonly CountSignalement $countSignalement,
        private readonly CountSuivi $countSuivi,
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
