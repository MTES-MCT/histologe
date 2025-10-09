<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TabCountKpiCacheHelper
{
    public const string NOUVEAUX_DOSSIERS = 'nouveaux_dossiers';
    public const string DOSSIERS_A_FERMER = 'dossiers_a_fermer';
    public const string DOSSIERS_MESSAGES_USAGERS = 'dossiers_messages_usagers';
    public const string DOSSIERS_A_VERIFIER = 'dossiers_a_verifier';

    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly ParameterBagInterface $parameterBag, )
    {
    }

    public function getOrSet(string $kpiName, User $user, ?TabQueryParameters $params, callable $callback): mixed
    {
        $key = $this->generateKey($kpiName, $user, $params);

        return $this->cache->get($key, function (ItemInterface $item) use ($callback) {
            $item->expiresAfter($this->parameterBag->get('tab_data_kpi_cache_expired_after'));

            return $callback();
        });
    }

    public function delete(string $kpiName, User $user, ?TabQueryParameters $params): bool
    {
        $key = $this->generateKey($kpiName, $user, $params);

        return $this->cache->delete($key);
    }

    private function generateKey(string $kpiName, User $user, ?TabQueryParameters $params): string
    {
        $roleKey = implode('-', array_filter($user->getRoles(), fn ($role) => 'ROLE_USER' !== $role));
        $territoryKey = $this->getTerritoryKey($user, $params);
        $partnerKey = $this->getPartnerKey($kpiName, $user, $params);
        $otherParamsKey = '';
        if ($params) {
            switch ($kpiName) {
                case self::NOUVEAUX_DOSSIERS:
                    $otherParams = null;
                    break;
                case self::DOSSIERS_A_FERMER:
                    $otherParams = null;
                    break;
                case self::DOSSIERS_MESSAGES_USAGERS:
                    $otherParams = [
                        'mesDossiersMessagesUsagers' => '1' === $params->mesDossiersMessagesUsagers ? $user->getId() : 'null',
                    ];
                    break;
                case self::DOSSIERS_A_VERIFIER:
                    $otherParams = [
                        'commune' => $params->queryCommune,
                        'mesDossiersAverifier' => '1' === $params->mesDossiersAverifier ? $user->getId() : 'null',
                    ];
                    break;
                default:
                    $otherParams = null;
            }
            if ($otherParams) {
                $otherParamsKey = '-'.implode(
                    '-',
                    array_map(
                        fn ($k, $v) => $k.'_'.$v,
                        array_keys($otherParams),
                        array_values($otherParams)
                    )
                );
            }
        }

        return 'tab_kpi_'.$kpiName.'-'.$roleKey.'-'.$territoryKey.'-'.$partnerKey.$otherParamsKey;
    }

    private function getTerritoryKey(User $user, ?TabQueryParameters $tabQueryParameters): string
    {
        $territories = [];
        if (1 === count($user->getPartnersTerritories()) || ($tabQueryParameters && $tabQueryParameters->territoireId)) {
            $territories[] = 'territory_'.($tabQueryParameters->territoireId ?? $user->getFirstTerritory()->getId());
        } elseif ($user->isSuperAdmin()) {
            $territories[] = 'territory_all';
        } else {
            foreach ($user->getPartnersTerritories() as $territory) {
                $territories[] = 'territory_'.$territory->getId();
            }
        }
        asort($territories);

        return implode('-', $territories);
    }

    private function getPartnerKey(string $kpiName, User $user, ?TabQueryParameters $tabQueryParameters): string
    {
        $partners = [];
        if (self::DOSSIERS_A_VERIFIER === $kpiName && $tabQueryParameters && $tabQueryParameters->partners) {
            foreach ($tabQueryParameters->partners as $partner) {
                $partners[] = 'partner_'.$partner;
            }
        } elseif ($tabQueryParameters && $tabQueryParameters->partenairesId) {
            foreach ($tabQueryParameters->partenairesId as $partnerId) {
                $partners[] = 'partner_'.$partnerId;
            }
        } elseif ($user->isSuperAdmin()) {
            $partners[] = 'partner_all';
        } elseif ($user->isTerritoryAdmin()) {
            $partners[] = 'partner_'.$user->getFirstTerritory().'-all';
        } else {
            foreach ($user->getPartners() as $partner) {
                $partners[] = 'partner_'.$partner->getId();
            }
        }
        asort($partners);

        return implode('-', $partners);
    }
}
