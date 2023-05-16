<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

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
            Response::HTTP_OK,
            10,
            10,
            PartnerType::COMMUNE_SCHS
        );

        $this->assertInstanceOf(JobEvent::class, $jobEvent);
        $this->assertEquals('success', $jobEvent->getStatus());
    }
}
