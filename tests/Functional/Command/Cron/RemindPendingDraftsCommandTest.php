<?php

namespace App\Tests\Functional\Command\Cron;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class RemindPendingDraftsCommandTest extends KernelTestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $dateTest = (new \DateTimeImmutable())->modify('first day of this month')->setTime(0, 0, 0);

        $sql = '
            UPDATE signalement_draft
            SET created_at = :created_at,
                updated_at = :updated_at,
                payload    = JSON_SET(payload, "$.info_procedure_bail_date", :bail_date),
                bailleur_prevenu_at = STR_TO_DATE(
                    CONCAT("01/", :bail_date),
                    "%d/%m/%Y"
                )
            WHERE uuid LIKE :uuid
        ';

        $connection->prepare($sql)->executeQuery([
            'created_at' => $dateTest->format('Y-m-d H:i:s'),
            'updated_at' => $dateTest->format('Y-m-d H:i:s'),
            'bail_date' => $dateTest->format('m/Y'), // "11/2024"
            'uuid' => '00000000-0000-0000-2023-tierspart002',
        ]);

        $container = self::getContainer();
        $mockClock = new MockClock($dateTest);
        $container->set(ClockInterface::class, $mockClock);

        $command = $application->find('app:remind-pending-drafts-bailleur-prevenu');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('0 usagers have been notified', $output);
        $this->assertEmailCount(1);

        $mockClock->modify('+3 months +1 day');

        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 usagers have been notified', $output);
        $this->assertEmailCount(3);
    }
}
