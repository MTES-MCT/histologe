<?php

namespace App\Service\Signalement;

use App\Entity\Enum\VisiteStatus;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\BailleurRepository;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\Query\Address\AddressesHistoryQuery;
use App\Repository\Query\Commune\CommuneEpciQuery;
use App\Repository\Query\Statistics\CountStatisticsQuery;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\ZoneRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SearchFilterOptionDataProvider
{
    public const CACHE_TAG = 'search-filters';

    public function __construct(
        private readonly CritereRepository $critereRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TagRepository $tagsRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly AddressesHistoryQuery $addressesHistoryQuery,
        private readonly TagAwareCacheInterface $cache,
        private readonly QualificationStatusService $qualificationStatusService,
        private readonly BailleurRepository $bailleurRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly CountStatisticsQuery $countStatisticsQuery,
        private readonly CommuneEpciQuery $communeEpciQuery,
    ) {
    }

    /**
     * @return array<mixed>
     *
     * @throws InvalidArgumentException
     */
    public function getData(User $user, ?Territory $territory = null, ?string $context = null): array
    {
        return $this->cache->get(
            $this->getCacheKey($user, $territory, $context),
            function (ItemInterface $item) use ($territory, $user, $context) {
                $item->expiresAfter(3600);

                $contextTag = (empty($context) ? '' : '-'.$context);

                if ($territory) {
                    $item->tag([self::CACHE_TAG.$contextTag.$territory->getZip()]);
                } else {
                    $item->tag([self::CACHE_TAG.$contextTag]);
                }

                $isAddressesHistoryContext = 'addresses-history' === $context;

                return [
                    'criteres' => $this->critereRepository->findAllList(),
                    'territories' => $this->getTerritories($user),
                    'addresses' => $isAddressesHistoryContext ? $this->addressesHistoryQuery->findAllList($territory) : [],
                    'partners' => $this->partnerRepository->findAllList($territory, $user),
                    'epcis' => $this->communeEpciQuery->findEpciByCommuneTerritory($territory, $user),
                    'tags' => $this->tagsRepository->findAllActive($territory, $user),
                    'zones' => $this->zoneRepository->findForUserAndTerritory($user, $territory),
                    'cities' => $this->signalementRepository->findCities($user, $territory),
                    'zipcodes' => $this->signalementRepository->findZipcodes($user, $territory),
                    'listQualificationStatus' => $this->qualificationStatusService->getList(),
                    'listVisiteStatus' => VisiteStatus::getLabelList(),
                    'hasSignalementsImported' => $user->isSuperAdmin() || $user->isTerritoryAdmin()
                        ? $this->countStatisticsQuery->countImported($territory) : $this->countStatisticsQuery->countImported($territory, $user),
                    'bailleursSociaux' => $isAddressesHistoryContext
                        ? $this->addressesHistoryQuery->findBailleursAndSyndics($user, $territory)
                        : $this->bailleurRepository->findBailleursByTerritory($user, $territory),
                ];
            }
        );
    }

    /**
     * @return array<mixed>
     */
    public function getTerritories(User $user): array
    {
        return $user->isSuperAdmin() ? $this->territoryRepository->findAllList(indexById: false) : $user->getPartnersTerritories(true);
    }

    private function getCacheKey(User $user, ?Territory $territory = null, ?string $context = null): string
    {
        $className = (new \ReflectionClass(__CLASS__))->getShortName();

        if ($user->isSuperAdmin()) {
            return $className.User::ROLE_ADMIN.'-territory-'.$territory?->getZip().'-context-'.$context;
        }
        $role = $user->getRoles();
        $partnersIds = implode('-', $user->getPartners()->map(static fn ($partner) => $partner->getId())->toArray());

        return $className.array_shift($role).'-partners-'.$partnersIds.'-territory-'.$territory?->getZip().'-context-'.$context;
    }
}
