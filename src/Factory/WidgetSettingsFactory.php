<?php

namespace App\Factory;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\DashboardWidget\WidgetSettings;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\UserAvatar;

class WidgetSettingsFactory
{
    public function __construct(
        private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        private readonly UserAvatar $userAvatar,
    ) {
    }

    public function createInstanceFrom(?User $user = null, ?Territory $territory = null): WidgetSettings
    {
        $filterOptionData = $this->searchFilterOptionDataProvider->getData($user, $territory);

        return new WidgetSettings(
            user: $user,
            territories: $filterOptionData['territories'],
            partners: $filterOptionData['partners'],
            communes: $this->getCommunesAndZipCodes($filterOptionData),
            epcis: $filterOptionData['epcis'],
            tags: $filterOptionData['tags'],
            hasSignalementImported: $filterOptionData['hasSignalementsImported'] > 0,
            bailleursSociaux: $filterOptionData['bailleursSociaux'],
            avatarOrPlaceHolder: $this->userAvatar->userAvatarOrPlaceHolder($user, 80)
        );
    }

    private function getCommunesAndZipCodes(array $filterOptionData): array
    {
        $suggestionsCommuneZipCode = [...$filterOptionData['cities'], ...$filterOptionData['zipcodes']];

        $suggestionsCommuneZipCode = array_map(
            fn ($suggestion): string => $suggestion['city'] ?? $suggestion['zipcode'] ?? '',
            $suggestionsCommuneZipCode
        );

        return array_filter($suggestionsCommuneZipCode);
    }
}
