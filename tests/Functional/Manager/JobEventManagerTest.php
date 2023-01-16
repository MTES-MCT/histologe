<?php

namespace App\Tests\Functional\Manager;

use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobEventManagerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testCreateJobEvent(): void
    {
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);

        $jobEventManager = new JobEventManager($managerRegistry, JobEvent::class);
        $jobEvent = $jobEventManager->createJobEvent(
            'esabora',
            'push_dossier',
            '',
            '',
            JobEvent::STATUS_SUCCESS,
            10,
            10
        );

        $this->assertInstanceOf(JobEvent::class, $jobEvent);
        $this->assertEquals('success', $jobEvent->getStatus());
    }
}
