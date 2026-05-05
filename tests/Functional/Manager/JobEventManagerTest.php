<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use Doctrine\ORM\EntityManagerInterface;
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
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);

        $jobEventManager = new JobEventManager($entityManager, $managerRegistry, JobEvent::class);
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

        $entityManager->flush();

        $this->assertInstanceOf(JobEvent::class, $jobEvent);
        $this->assertEquals('success', $jobEvent->getStatus());
    }
}
