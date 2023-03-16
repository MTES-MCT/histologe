<?php

namespace App\Service\Signalement;

use App\Entity\User;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SearchFilterOptionDataProvider
{
    public function __construct(
        private readonly CritereRepository $critereRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TagRepository $tagsRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getData(?User $user = null): array
    {
        $user = $user?->isUserPartner() || $user?->isPartnerAdmin() ? $user : null;
        $territory = !$user?->isSuperAdmin() ? $user?->getTerritory() : null;

        return $this->cache->get(
            null === $user ? __FUNCTION__.'_all_user' : __FUNCTION__.'_partner_user',
            function (ItemInterface $item) use ($territory, $user) {
                $item->expiresAfter(3600);

                return [
                    'criteres' => $this->critereRepository->findAllList(),
                    'territories' => $this->territoryRepository->findAllList(),
                    'partners' => $this->partnerRepository->findAllList($territory),
                    'tags' => $this->tagsRepository->findAllActive($territory),
                    'cities' => $this->signalementRepository->findCities($user, $territory),
                ];
            });
    }
}
