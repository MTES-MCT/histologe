<?php

namespace App\Service\Signalement;

use App\Entity\Enum\VisiteStatus;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\BailleurRepository;
use App\Repository\CommuneRepository;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
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
        private readonly CommuneRepository $communeRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TagRepository $tagsRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly TagAwareCacheInterface $cache,
        private readonly QualificationStatusService $qualificationStatusService,
        private readonly BailleurRepository $bailleurRepository,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getData(User $user, ?Territory $territory = null): array
    {
        return $this->cache->get(
            $this->getCacheKey($user, $territory),
            function (ItemInterface $item) use ($territory, $user) {
                $item->expiresAfter(3600);

                if ($territory) {
                    $item->tag([self::CACHE_TAG.$territory->getZip()]);
                } else {
                    $item->tag([self::CACHE_TAG]);
                }

                return [
                    'criteres' => $this->critereRepository->findAllList(),
                    'territories' => $user->isSuperAdmin() ? $this->territoryRepository->findAllList() : $user->getPartnersTerritories(true),
                    'partners' => $this->partnerRepository->findAllList($territory, $user),
                    'epcis' => $this->communeRepository->findEpciByCommuneTerritory($territory, $user),
                    'tags' => $this->tagsRepository->findAllActive($territory, $user),
                    'zones' => $this->zoneRepository->findForUserAndTerritory($user, $territory),
                    'cities' => $this->signalementRepository->findCities($user, $territory),
                    'zipcodes' => $this->signalementRepository->findZipcodes($user, $territory),
                    'listQualificationStatus' => $this->qualificationStatusService->getList(),
                    'listVisiteStatus' => VisiteStatus::getLabelList(),
                    'hasSignalementsImported' => $this->signalementRepository->countImported($territory, $user),
                    'bailleursSociaux' => $this->bailleurRepository->findBailleursByTerritory($user, $territory),
                ];
            }
        );
    }

    private function getCacheKey(User $user, ?Territory $territory = null): string
    {
        $className = (new \ReflectionClass(__CLASS__))->getShortName();

        if ($user->isSuperAdmin()) {
            return $className.User::ROLE_ADMIN.'-territory-'.$territory?->getZip();
        }
        $role = $user->getRoles();
        $partnersIds = implode('-', $user->getPartners()->map(fn ($partner) => $partner->getId())->toArray());

        return $className.array_shift($role).'-partners-'.$partnersIds.'-territory-'.$territory?->getZip();
    }
}
