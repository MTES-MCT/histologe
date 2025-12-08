<?php

namespace App\Factory;

use App\Dto\Settings;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\UserSearchFilterRepository;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\UserAvatar;
use Psr\Cache\InvalidArgumentException;

class SettingsFactory
{
    public function __construct(
        private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        private readonly UserAvatar $userAvatar,
        private readonly UserSearchFilterRepository $userSearchFilterRepository,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createInstanceFrom(User $user, ?Territory $territory = null): Settings
    {
        $filterOptionData = $this->searchFilterOptionDataProvider->getData($user, $territory);

        return new Settings(
            user: $user,
            territories: $filterOptionData['territories'],
            partners: $filterOptionData['partners'],
            communes: $this->getCommunesAndZipCodes($filterOptionData),
            epcis: $filterOptionData['epcis'],
            tags: $filterOptionData['tags'],
            zones: $filterOptionData['zones'],
            hasSignalementImported: $filterOptionData['hasSignalementsImported'] > 0,
            bailleursSociaux: $filterOptionData['bailleursSociaux'],
            avatarOrPlaceHolder: $this->userAvatar->userAvatarOrPlaceHolder($user, 80),
            savedSearches: $this->userSearchFilterRepository->findAllForUserArray($user),
        );
    }

    /**
     * @param array<string, mixed> $filterOptionData
     *
     * @return array<int, string>
     */
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
