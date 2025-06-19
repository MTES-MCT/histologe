<?php

namespace App\Service\DashboardWidget;

use App\Dto\CountPartner;
use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use Symfony\Component\Serializer\Attribute\Groups;

class WidgetDataKpi
{
    /**
     * @var array<string, WidgetCard>
     */
    private readonly array $widgetCards;
    /**
     * @param array<string, WidgetCard> $widgetCards
     */
    public function __construct(
        array $widgetCards,
        #[Groups(['widget:read'])]
        private readonly CountSignalement $countSignalement,
        #[Groups(['widget:read'])]
        private readonly CountSuivi $countSuivi,
        #[Groups(['widget:read'])]
        private readonly CountUser $countUser,
        #[Groups(['widget:read'])]
        private readonly CountPartner $countPartner,
    ) {
        $this->widgetCards = $widgetCards;
    }

    /**
     * @return array<string, WidgetCard>
     */
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

    public function getCountPartner(): CountPartner
    {
        return $this->countPartner;
    }
}
