<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\EventSubscriber\CacheInvalidationSubscriber;
use App\Service\CacheCommonKeyGenerator;
use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheInvalidationSubscriberTest extends TestCase
{
    private TagAwareCacheInterface $dashboardCache;
    private CacheCommonKeyGenerator $cacheCommonKeyGenerator;
    private LoggerInterface $logger;
    private Security $security;
    private ?CacheInvalidationSubscriber $cacheInvalidationSubscriber = null;

    protected function setUp(): void
    {
        $this->dashboardCache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheCommonKeyGenerator = $this->createMock(CacheCommonKeyGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->cacheInvalidationSubscriber = new CacheInvalidationSubscriber(
            $this->dashboardCache,
            $this->cacheCommonKeyGenerator,
            $this->logger,
            $this->security,
        );
    }

    public function testSupports()
    {
        $this->assertTrue($this->cacheInvalidationSubscriber->supports(new Signalement()));
        $this->assertTrue($this->cacheInvalidationSubscriber->supports(new Notification()));
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertTrue(\in_array(
            Events::postPersist,
            $this->cacheInvalidationSubscriber->getSubscribedEvents()
        ));

        $this->assertTrue(\in_array(
            Events::postUpdate,
            $this->cacheInvalidationSubscriber->getSubscribedEvents()
        ));

        $this->assertTrue(\in_array(
            Events::postRemove,
            $this->cacheInvalidationSubscriber->getSubscribedEvents()
        ));
    }
}
