<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\InterfacageType;
use App\Entity\Signalement;
use App\Repository\JobEventRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use App\Service\ListFilters\SearchInterconnexion;
use Doctrine\ORM\EntityManagerInterface;
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
        $this->assertEquals(4, $failedCount);
    }

    public function testFindLastJobEventByInterfacageType(): void
    {
        $container = static::getContainer();
        $jobEventRepository = $container->get(JobEventRepository::class);

        $jobEvents = $jobEventRepository->findLastJobEventByInterfacageType(
            'esabora',
            7,
            []
        );

        $this->assertCount(8, $jobEvents);
    }

    public function testFindLastJobEventByTerritoryWithReference(): void
    {
        $container = static::getContainer();
        /** @var JobEventRepository $jobEventRepository */
        $jobEventRepository = $container->get(JobEventRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-9']);

        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setReference($signalement->getReference());

        $jobEvents = $jobEventRepository->findLastJobEventByTerritory(
            365,
            $searchInterconnexion,
            10,
            0
        );

        $this->assertCount(5, $jobEvents);
        $this->assertEquals($signalement->getReference(), $jobEvents[0]['reference']);
    }

    public function testFindLastJobEventByTerritoryWithAction(): void
    {
        $container = static::getContainer();
        /** @var JobEventRepository $jobEventRepository */
        $jobEventRepository = $container->get(JobEventRepository::class);

        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setAction(EsaboraSCHSService::ACTION_PUSH_DOSSIER);

        $jobEvents = $jobEventRepository->findLastJobEventByTerritory(
            365,
            $searchInterconnexion,
            10,
            0
        );

        $this->assertCount(4, $jobEvents);
        $this->assertEquals(AbstractEsaboraService::ACTION_PUSH_DOSSIER, $jobEvents[0]['action']);
    }

    public function testFindFailedJobEvents(): void
    {
        $container = static::getContainer();
        $jobEventRepository = $container->get(JobEventRepository::class);

        $jobEvents = $jobEventRepository->findFailedJobEvents(
            InterfacageType::ESABORA->value,
            AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE
        );

        $this->assertCount(1, $jobEvents);
    }
}
