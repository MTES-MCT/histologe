<?php

namespace App\EventListener;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\CacheCommonKeyGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class CacheInvalidationListener
{
    public const CONTEXT_WIDGET_DATA_KPI = 'countDataKpi';

    public function __construct(
        readonly private TagAwareCacheInterface $dashboardCache,
        readonly private CacheCommonKeyGenerator $cacheCommonKeyGenerator,
        readonly private LoggerInterface $logger,
        private readonly Security $security,
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheWidgetDataKpi($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheWidgetDataKpi($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheWidgetDataKpi($args);
    }

    public function supports(mixed $entity): bool
    {
        return $entity instanceof Signalement || $entity instanceof Notification;
    }

    private function invalidateCacheWidgetDataKpi(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->supports($entity)) {
            /** @var User $user */
            $user = $this->security->getUser();
            $territory = $user?->getTerritory();
            try {
                if ($entity instanceof Signalement) {
                    $this->dashboardCache->invalidateTags(['data-kpi-'.$territory?->getZip()]);
                } else {
                    $commonKey = $this->cacheCommonKeyGenerator->generate();
                    $key = self::CONTEXT_WIDGET_DATA_KPI
                        .'-'.$commonKey
                        .'-zip-'.$territory?->getZip()
                        .'-id-'.$user?->getId();
                    $this->dashboardCache->delete($key);
                }
            } catch (InvalidArgumentException $exception) {
                $this->logger->error(sprintf('Invalidate cache failed %s', $exception->getMessage()));
            }
        }
    }
}
