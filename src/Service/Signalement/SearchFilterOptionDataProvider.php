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
use App\Service\Signalement\Qualification\QualificationStatusService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SearchFilterOptionDataProvider
{
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
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getData(?User $user = null, ?Territory $territory = null): array
    {
        if (null === $territory) {
            $territory = !$user?->isSuperAdmin() ? $user?->getTerritory() : null;
        }

        return $this->cache->get(
            $this->getCacheKey($user, $territory),
            function (ItemInterface $item) use ($territory, $user) {
                $item->expiresAfter(3600);
                if ($territory) {
                    $item->tag([$territory->getZip()]);
                }

                return [
                    'criteres' => $this->critereRepository->findAllList(),
                    'territories' => $this->territoryRepository->findAllList(),
                    'partners' => $this->partnerRepository->findAllList($territory),
                    'epcis' => $this->communeRepository->findEpciByCommuneTerritory($territory),
                    'tags' => $this->tagsRepository->findAllActive($territory),
                    'cities' => $this->signalementRepository->findCities($user, $territory),
                    'zipcodes' => $this->signalementRepository->findZipcodes($user, $territory),
                    'listQualificationStatus' => $this->qualificationStatusService->getList(),
                    'listVisiteStatus' => VisiteStatus::getLabelList(),
                    'hasSignalementsImported' => $this->signalementRepository->countImported($territory, $user),
                    'bailleursSociaux' => $this->bailleurRepository->findBailleursByTerritory($territory?->getZip()),
                ];
            }
        );
    }

    private function getCacheKey(?User $user, ?Territory $territory = null): string
    {
        $className = (new \ReflectionClass(__CLASS__))->getShortName();

        if (null == $user) {
            return $className.User::ROLE_ADMIN;
        }
        $role = $user->getRoles();
        $territory = !$user?->isSuperAdmin() ? $user?->getTerritory() : $territory;
        $partner = !$user?->isSuperAdmin() ? $user?->getPartner() : null;

        return $className.array_shift($role).'-partner-'.$partner?->getId().'-territory-'.$territory?->getZip();
    }
}
