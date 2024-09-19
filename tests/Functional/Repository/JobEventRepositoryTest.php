<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\PartnerType;
use App\Repository\JobEventRepository;
use App\Service\Esabora\AbstractEsaboraService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobEventRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testGetReportEsaboraIntervention(): void
    {
        $container = static::getContainer();
        $jobEventRepository = $container->get(JobEventRepository::class);

        ['success_count' => $successCount, 'failed_count' => $failedCount] =
            $jobEventRepository->getReportEsaboraAction(
                AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                AbstractEsaboraService::ACTION_SYNC_DOSSIER);

        $this->assertEquals(4, $successCount);
        $this->assertEquals(3, $failedCount);
    }

    public function testFindLastJobEventByInterfacageType(): void
    {
        $container = static::getContainer();
        $jobEventRepository = $container->get(JobEventRepository::class);

        $jobEvents = $jobEventRepository->findLastJobEventByInterfacageType(
            'esabora',
            7,
            null
        );

        $this->assertCount(2, $jobEvents);
    }

    public function testFindFailedEsaboraDossierByPartnerTypeByAction(): void
    {
        $container = static::getContainer();
        $jobEventRepository = $container->get(JobEventRepository::class);

        $jobEvents = $jobEventRepository->findFailedEsaboraDossierByPartnerTypeByAction(
            PartnerType::ARS,
            'push_dossier'
        );

        $this->assertCount(3, $jobEvents);
    }
}