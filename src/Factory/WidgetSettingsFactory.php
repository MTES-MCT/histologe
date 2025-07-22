<?php

namespace App\Factory;

use App\Entity\Territory;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\DashboardWidget\WidgetSettings;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use App\Service\UserAvatar;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @todo Rename class to SettingsFactory once the FEATURE_NEW_DASHBOARD feature flag is removed.
 */
class WidgetSettingsFactory
{
    public function __construct(
        private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        private readonly Security $security,
        private readonly UserAvatar $userAvatar,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')]
        private readonly bool $featureNewDashboard, // remove when FEATURE_NEW_DASHBOARD active
    ) {
    }

    public function createInstanceFrom(User $user, ?Territory $territory = null): WidgetSettings
    {
        $filterOptionData = $this->searchFilterOptionDataProvider->getData($user, $territory);

        return new WidgetSettings(
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
            avatarOrPlaceHolder: $this->userAvatar->userAvatarOrPlaceHolder($user, 80),
            isFeatureNewDashboard: $this->featureNewDashboard,
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
