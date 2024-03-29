<?php

namespace App\Service\Signalement;

use App\Entity\Enum\VisiteStatus;
use App\Entity\User;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\Qualification\QualificationStatusService;
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
        private readonly QualificationStatusService $qualificationStatusService,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getData(?User $user = null): array
    {
        $territory = !$user?->isSuperAdmin() ? $user?->getTerritory() : null;

        return $this->cache->get(
            $this->getCacheKey($user),
            function (ItemInterface $item) use ($territory, $user) {
                $item->expiresAfter(3600);

                return [
                    'criteres' => $this->critereRepository->findAllList(),
                    'territories' => $this->territoryRepository->findAllList(),
                    'partners' => $this->partnerRepository->findAllList($territory),
                    'tags' => $this->tagsRepository->findAllActive($territory),
                    'cities' => $this->signalementRepository->findCities($user, $territory),
                    'listQualificationStatus' => $this->qualificationStatusService->getList(),
                    'listVisiteStatus' => VisiteStatus::getLabelList(),
                ];
            }
        );
    }

    private function getCacheKey(?User $user): string
    {
        $className = (new \ReflectionClass(__CLASS__))->getShortName();

        if (null == $user) {
            return $className.User::ROLE_ADMIN;
        }
        $role = $user->getRoles();
        $territory = !$user?->isSuperAdmin() ? $user?->getTerritory() : null;
        $partner = !$user?->isSuperAdmin() ? $user?->getPartner() : null;

        return $className.array_shift($role).'-partner-'.$partner?->getId().'-territory-'.$territory?->getZip();
    }
}
