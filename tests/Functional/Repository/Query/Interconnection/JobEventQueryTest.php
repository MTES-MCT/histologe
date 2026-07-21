<?php

namespace App\Tests\Functional\Repository\Query\Interconnection;

use App\Repository\Query\Interconnection\JobEventQuery;
use App\Repository\SignalementRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobEventQueryTest extends KernelTestCase
{
    private JobEventQuery $jobEventQuery;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->jobEventQuery = static::getContainer()->get(JobEventQuery::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    #[DataProvider('provideSignalementReferences')]
    public function testFindSyncStatusesForSignalement(
        string $reference,
        string $expectedAction,
        string $assertionMessage,
    ): void {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $reference]);
        $this->assertNotNull($signalement);

        $syncStatuses = $this->jobEventQuery->findSyncStatusesForSignalement($signalement);

        $this->assertIsArray($syncStatuses);
        $this->assertNotEmpty($syncStatuses);

        foreach ($syncStatuses as $partnerId => $status) {
            $this->assertIsInt($partnerId);
            $this->assertArrayHasKey('last_job_event_action', $status);
            $this->assertArrayHasKey('last_job_event_response', $status);
        }

        $hasExpectedEvent = false;
        foreach ($syncStatuses as $status) {
            if ($expectedAction === $status['last_job_event_action']) {
                $hasExpectedEvent = true;
                break;
            }
        }
        $this->assertTrue($hasExpectedEvent, $assertionMessage);
    }

    public static function provideSignalementReferences(): \Generator
    {
        yield 'signalement 2023-9 with failed idoss push_dossier' => [
            'reference' => '2023-9',
            'expectedAction' => 'push_dossier',
            'assertionMessage' => 'Should have at least one failed push_dossier event for signalement 2023-9',
        ];

        yield 'signalement 2023-10 with failed push_dossier_adresse' => [
            'reference' => '2023-10',
            'expectedAction' => 'push_dossier_adresse',
            'assertionMessage' => 'Should have a failed push_dossier_adresse event for signalement 2023-10',
        ];
    }
}
