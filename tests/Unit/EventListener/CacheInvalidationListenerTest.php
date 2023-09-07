<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\Notification;
use App\Entity\Signalement;
use App\EventListener\CacheInvalidationListener;
use App\Service\CacheCommonKeyGenerator;
use Doctrine\ORM\Events;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheInvalidationListenerTest extends TestCase
{
    private TagAwareCacheInterface $dashboardCache;
    private CacheCommonKeyGenerator $cacheCommonKeyGenerator;
    private LoggerInterface $logger;
    private Security $security;
    private ?CacheInvalidationListener $cacheInvalidationListener = null;

    protected function setUp(): void
    {
        $this->dashboardCache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheCommonKeyGenerator = $this->createMock(CacheCommonKeyGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->cacheInvalidationListener = new CacheInvalidationListener(
            $this->dashboardCache,
            $this->cacheCommonKeyGenerator,
            $this->logger,
            $this->security,
        );
    }

    public function testSupports()
    {
        $this->assertTrue($this->cacheInvalidationListener->supports(new Signalement()));
        $this->assertTrue($this->cacheInvalidationListener->supports(new Notification()));
    }

    public function testGetSubscribedEvents(): void
    {
        $reflection = new \ReflectionClass(CacheInvalidationListener::class);
        $events = array_map(function (\ReflectionAttribute $attribute) {
            return $attribute->getArguments()['event'];
        }, $reflection->getAttributes());

        $this->assertTrue(\in_array(
            Events::postPersist,
            $events
        ));

        $this->assertTrue(\in_array(
            Events::postUpdate,
            $events
        ));

        $this->assertTrue(\in_array(
            Events::postRemove,
            $events
        ));
    }
}
