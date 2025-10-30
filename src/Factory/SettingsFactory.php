<?php

namespace App\Factory;

use App\Dto\Settings;
use App\Entity\Territory;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\UserAvatar;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

class SettingsFactory
{
    public function __construct(
        private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        private readonly Security $security,
        private readonly UserAvatar $userAvatar,
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
            canSeeNDE: $this->security->isGranted(UserVoter::SEE_NDE, $user),
            partners: $filterOptionData['partners'],
            communes: $this->getCommunesAndZipCodes($filterOptionData),
            epcis: $filterOptionData['epcis'],
            tags: $filterOptionData['tags'],
            zones: $filterOptionData['zones'],
            hasSignalementImported: $filterOptionData['hasSignalementsImported'] > 0,
            bailleursSociaux: $filterOptionData['bailleursSociaux'],
            avatarOrPlaceHolder: $this->userAvatar->userAvatarOrPlaceHolder($user, 80)
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
