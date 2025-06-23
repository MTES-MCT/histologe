<?php

namespace App\Service\DashboardWidget;

use App\Entity\User;
use App\Service\CacheCommonKeyGenerator;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class WidgetDataManagerCache implements WidgetDataManagerInterface
{
    private ?string $commonKey = null;

    public function __construct(
        private readonly WidgetDataManager $widgetDataManager,
        private readonly TagAwareCacheInterface $dashboardCache,
        private readonly Security $security,
        private readonly CacheCommonKeyGenerator $cacheCommonKeyGenerator,
        readonly private LoggerInterface $logger,
    ) {
        $this->initKeyCache();
    }

    private function initKeyCache(): void
    {
        $this->commonKey = $this->cacheCommonKeyGenerator->generate().'-zip-';
    }

    /**
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
     */
    public function countSignalementAcceptedNoSuivi(array $territories, ?array $params = null): array
    {
        $territoriesKey = implode('-', array_keys($territories));

        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->commonKey.$territoriesKey,
            function (ItemInterface $item) use ($territories, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countSignalementAcceptedNoSuivi($territories);
            }
        );
    }

    /**
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
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
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     *
     * @return array<mixed>
     */
    public function countAffectationPartner(array $territories, ?array $params = null): array
    {
        $territoriesKey = implode('-', array_keys($territories));

        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->commonKey.$territoriesKey,
            function (ItemInterface $item) use ($territories, $params) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countAffectationPartner($territories);
            }
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<int, mixed>    $territories
     *
     * @return array<mixed>
     */
    public function findLastJobEventByInterfacageType(string $type, array $params, array $territories): array
    {
        $territoriesKey = implode('-', array_keys($territories));

        return $this->dashboardCache->get(
            __FUNCTION__.'-'.$this->commonKey.$territoriesKey,
            function (ItemInterface $item) use ($type, $params, $territories) {
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->findLastJobEventByInterfacageType($type, $params, $territories);
            }
        );
    }

    /**
     * @param array<int, mixed>         $territories
     * @param array<string, mixed>|null $params
     */
    public function countDataKpi(array $territories, ?array $params = null): WidgetDataKpi
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territoriesKey = implode('-', array_keys($territories));
        $key = __FUNCTION__.'-'.$this->commonKey.$territoriesKey.'-id-'.$user->getId();

        return $this->dashboardCache->get(
            $key,
            function (ItemInterface $item) use ($params, $territories, $territoriesKey) {
                $item->tag('data-kpi-'.$territoriesKey);
                $item->expiresAfter($params['expired_after'] ?? null);

                return $this->widgetDataManager->countDataKpi($territories, $params);
            }
        );
    }

    /**
     * @param array<int, mixed> $territories
     *
     * @throws InvalidArgumentException
     */
    public function invalidateCacheForUser(array $territories): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territoriesKey = implode('-', array_keys($territories));
        $key = 'countDataKpi'
            .'-'.$this->commonKey.$territoriesKey
            .'-id-'.$user->getId();

        try {
            $this->dashboardCache->delete($key);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error(\sprintf('Invalidate cache failed %s', $exception->getMessage()));
        }
    }
}
