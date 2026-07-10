<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository\Query\Interconnection;

use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Repository\AffectationRepository;
use App\Repository\Query\Interconnection\JobEventQuery;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\ListFilters\SearchInterconnexion;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $jobEventQuery = $container->get(JobEventQuery::class);

        ['success_count' => $successCount, 'failed_count' => $failedCount] =
            $jobEventQuery->getReportEsaboraAction(
                AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                AbstractEsaboraService::ACTION_SYNC_DOSSIER);

        $this->assertEquals(4, $successCount);
        $this->assertEquals(4, $failedCount);
    }

    public function testFindLastJobEventByTerritoryWithReference(): void
    {
        $container = static::getContainer();
        /** @var JobEventQuery $jobEventQuery */
        $jobEventQuery = $container->get(JobEventQuery::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-9']);

        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setReference($signalement->getReference());

        $jobEvents = $jobEventQuery->findLastByTerritory(
            365,
            $searchInterconnexion,
            10,
            0
        );

        $this->assertCount(4, $jobEvents);
        $this->assertEquals($signalement->getReference(), $jobEvents[0]['reference']);
    }

    public function testFindLastJobEventByTerritoryWithAction(): void
    {
        $container = static::getContainer();
        /** @var JobEventQuery $jobEventQuery */
        $jobEventQuery = $container->get(JobEventQuery::class);

        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setAction(AbstractEsaboraService::ACTION_PUSH_DOSSIER);
        $searchInterconnexion->setShowOnlyDataErrors(false);

        $jobEvents = $jobEventQuery->findLastByTerritory(
            365,
            $searchInterconnexion,
            10,
            0
        );

        $this->assertCount(4, $jobEvents);
        $this->assertEquals(AbstractEsaboraService::ACTION_PUSH_DOSSIER, $jobEvents[0]['action']);
    }

    public function testFindLastJobEventByTerritoryShowingOnlyDataErrors(): void
    {
        $container = static::getContainer();
        /** @var JobEventQuery $jobEventQuery */
        $jobEventQuery = $container->get(JobEventQuery::class);

        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setShowOnlyDataErrors(true);

        $jobEvents = $jobEventQuery->findLastByTerritory(
            365,
            $searchInterconnexion,
            100,
            0
        );

        foreach ($jobEvents as $jobEvent) {
            $response = $jobEvent['response'];
            $status = $jobEvent['status'];
            $this->assertTrue(
                'success' === $status
                || str_contains($response, 'WS_ERR_MOD_VERIFKEY')
                || str_contains($response, 'WS_ERR_DOCUMENT_SIZE')
                || str_contains($response, 'stream_get_meta_data')
            );
        }
    }

    /**
     * @param array<int, PartnerType> $partnerTypes
     * @param array<int, string>      $actions
     */
    #[DataProvider('provideDataForFailedJobEvents')]
    public function testFindAffectationsWithFailedJobEvents(
        string $interfacageType,
        array $actions,
        array $partnerTypes,
        int $expectedCount,
    ): void {
        $container = static::getContainer();
        $affectationRepository = $container->get(AffectationRepository::class);

        $affectationsWithFailedJobEvents = $affectationRepository->findAffectationsWithFailedJobEvents(
            $interfacageType,
            $actions,
            $partnerTypes
        );

        $this->assertCount($expectedCount, $affectationsWithFailedJobEvents);
    }

    public static function provideDataForFailedJobEvents(): \Generator
    {
        yield 'sish - push dossier adresse' => [
            InterfacageType::ESABORA->value,
            [
                AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE,
                AbstractEsaboraService::ACTION_PUSH_DOSSIER,
                AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE,
            ],
            [PartnerType::ARS, PartnerType::COMMUNE_SCHS],
            1,
        ];

        yield 'schs - push dossier SCHS' => [
            InterfacageType::ESABORA->value,
            [AbstractEsaboraService::ACTION_PUSH_DOSSIER],
            [PartnerType::COMMUNE_SCHS],
            0,
        ];

        yield 'idoss - push dossier IDOSS' => [
            InterfacageType::IDOSS->value,
            [IdossService::ACTION_PUSH_DOSSIER],
            [],
            1,
        ];
    }
}
