<?php

namespace App\Service\DashboardWidget;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\CacheCommonKeyGenerator;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WidgetDataManagerCache implements WidgetDataManagerInterface
{
    private ?string $commonKey = null;

    public function __construct(
        private WidgetDataManager $widgetDataManager,
        private CacheInterface $dashboardCache,
        private Security $security,
        private CacheCommonKeyGenerator $cacheCommonKeyGenerator
    ) {
        $this->initKeyCache();
    }

    private function initKeyCache(): void
    {
        $this->commonKey = $this->cacheCommonKeyGenerator->generate();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function countSignalementAcceptedNoSuivi(Territory $territory, ?array $params = null): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.$this->commonKey.'-zip-'.$territory?->getZip(),
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
            __FUNCTION__.'-'.$this->commonKey,
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
            __FUNCTION__.'-'.$this->commonKey.'-zip-'.$territory?->getZip(),
            function (ItemInterface $item) use ($territory, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countAffectationPartner($territory);
            }
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findLastJobEventByInterfacageType(string $type, array $params, ?Territory $territory = null): array
    {
        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->commonKey.'-zip-'.$territory?->getZip(),
            function (ItemInterface $item) use ($type, $params, $territory) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->findLastJobEventByInterfacageType($type, $params, $territory);
            }
        );
    }

    /**
     * @throws QueryException
     * @throws NonUniqueResultException
     * @throws Exception
     * @throws NoResultException
     */
    public function countDataKpi(?Territory $territory = null, ?array $params = null): WidgetDataKpi
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $key = __FUNCTION__.'-'.$this->commonKey.'-zip-'.$territory?->getZip().'-id-'.$user->getId();

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($params, $territory) {
                $item->expiresAfter(3600);

                return $this->widgetDataManager->countDataKpi($territory, $params);
            });
    }
}
