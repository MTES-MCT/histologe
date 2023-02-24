<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;
use App\Entity\User;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WidgetDataManagerCache implements WidgetDataManagerInterface
{
    private ?string $key = null;

    public function __construct(
        private WidgetDataManager $widgetDataManager,
        private CacheInterface $dashboardCache,
        private Security $security,
    ) {
        $this->initKeyCache();
    }

    private function initKeyCache(): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $role = $user->getRoles();
        $this->key = array_shift($role).'-partnerId-'.$user->getPartner()->getId();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countSignalementAcceptedNoSuivi(Territory $territory, ?array $params = null): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.$this->key.'-zip-'.$territory?->getZip(),
            function (ItemInterface $item) use ($territory, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countSignalementAcceptedNoSuivi($territory);
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countSignalementsByTerritory(?array $params = null): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->key,
            function (ItemInterface $item) use ($params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countSignalementsByTerritory();
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countAffectationPartner(?Territory $territory = null, ?array $params = null): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->key.'-zip-'.$territory?->getZip(),
            function (ItemInterface $item) use ($territory, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countAffectationPartner($territory);
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findLastJobEventByType(string $type, array $params): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->key,
            function (ItemInterface $item) use ($type, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->findLastJobEventByType($type, $params);
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countDataKpi(?Territory $territory = null, ?array $params = null): WidgetDataKpi
    {
        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->key.'-zip-'.$territory?->getZip(),
            function (ItemInterface $item) use ($territory, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countDataKpi($territory);
            }
        );
    }
}
