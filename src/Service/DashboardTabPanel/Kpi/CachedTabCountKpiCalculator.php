<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: TabCountKpiCalculatorInterface::class)]
class CachedTabCountKpiCalculator implements TabCountKpiCalculatorInterface
{
    public function __construct(
        private readonly TabCountKpiCalculatorInterface $calculator,
        private readonly TabCountKpiCacheHelper $cacheHelper,
    ) {
    }

    /**
     * @param array<int, mixed> $territories
     *
     * @throws InvalidArgumentException
     */
    public function countNouveauxDossiers(array $territories, User $user): CountNouveauxDossiers
    {
        return $this->cacheHelper->getOrSet(
            TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
            $user,
            null,
            fn () => $this->calculator->countNouveauxDossiers($territories, $user)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countDossiersAFermer(User $user, TabQueryParameters $params): CountAfermer
    {
        return $this->cacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
            $user,
            $params,
            fn () => $this->calculator->countDossiersAFermer($user, $params)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countDossiersMessagesUsagers(User $user, TabQueryParameters $params): CountDossiersMessagesUsagers
    {
        return $this->cacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS,
            $user,
            $params,
            fn () => $this->calculator->countDossiersMessagesUsagers($user, $params)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countDossiersAVerifier(User $user, TabQueryParameters $params): CountDossiersAVerifier
    {
        return $this->cacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
            $user,
            $params,
            fn () => $this->calculator->countDossiersAVerifier($user, $params)
        );
    }
}
