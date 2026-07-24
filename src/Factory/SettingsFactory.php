<?php

namespace App\Factory;

use App\Dto\Settings;
use App\Entity\Enum\ArreteType;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\UserSearchFilterRepository;
use App\Security\Voter\InjonctionBailleurVoter;
use App\Service\Files\UserAvatar;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

class SettingsFactory
{
    public function __construct(
        private readonly SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        private readonly UserAvatar $userAvatar,
        private readonly UserSearchFilterRepository $userSearchFilterRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createInstanceFrom(User $user, ?Territory $territory = null, ?string $context = null): Settings
    {
        $filterOptionData = $this->searchFilterOptionDataProvider->getData($user, $territory, $context);

        $isAddressesHistoryContext = 'addresses-history' === $context;

        return new Settings(
            user: $user,
            territories: $filterOptionData['territories'],
            partners: $filterOptionData['partners'],
            addresses: $filterOptionData['addresses'],
            communes: $this->getCommunesAndZipCodes($filterOptionData, $territory, $isAddressesHistoryContext),
            epcis: $filterOptionData['epcis'],
            tags: $filterOptionData['tags'],
            personalTags: $user->getPersonalTags()->toArray(),
            zones: $filterOptionData['zones'],
            hasSignalementImported: $filterOptionData['hasSignalementsImported'] > 0,
            hasInjonction: $this->security->isGranted(InjonctionBailleurVoter::INJONCTION_BAILLEUR_SEE),
            bailleursSociaux: $filterOptionData['bailleursSociaux'],
            avatarOrPlaceHolder: $this->userAvatar->userAvatarOrPlaceHolder($user, 80),
            savedSearches: $this->userSearchFilterRepository->findAllForUserArray($user),
            arretesTypes: $this->getArretesTypesGrouped(),
        );
    }

    /**
     * @param array<string, mixed> $filterOptionData
     *
     * @return array<int, string>
     */
    private function getCommunesAndZipCodes(array $filterOptionData, ?Territory $territory = null, bool $isAddressesHistoryContext = false): array
    {
        // If a territory is selected, only return its communes and zips
        if (!empty($territory)) {
            $suggestionsCommuneZipCode = [];
            $communes = $territory->getCommunes();
            foreach ($communes as $commune) {
                $suggestionsCommuneZipCode[] = $commune->getNom();
                if (!$isAddressesHistoryContext) {
                    $suggestionsCommuneZipCode[] = $commune->getCodePostal();
                }
            }
            $suggestionsCommuneZipCode = array_unique($suggestionsCommuneZipCode);

            return $suggestionsCommuneZipCode;
        }

        // Otherwise, return all available communes and zips from existing signalements
        if ($isAddressesHistoryContext) {
            $suggestionsCommuneZipCode = [...$filterOptionData['cities']];
        } else {
            $suggestionsCommuneZipCode = [...$filterOptionData['cities'], ...$filterOptionData['zipcodes']];
        }

        if ($isAddressesHistoryContext) {
            $suggestionsCommuneZipCode = array_map(
                static fn ($suggestion): string => $suggestion['city'] ?? '',
                $suggestionsCommuneZipCode
            );
        } else {
            $suggestionsCommuneZipCode = array_map(
                static fn ($suggestion): string => $suggestion['city'] ?? $suggestion['zipcode'] ?? '',
                $suggestionsCommuneZipCode
            );
        }

        return array_filter($suggestionsCommuneZipCode);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function getArretesTypesGrouped(): array
    {
        $choices = ArreteType::getChoices();
        $result = [];

        foreach ($choices as $groupTitle => $arretes) {
            $result[$groupTitle] = array_map(
                static fn (ArreteType $arrete): array => [
                    'Id' => $arrete->value,
                    'Text' => $arrete->completeLabel(),
                ],
                $arretes
            );
        }

        return $result;
    }
}
