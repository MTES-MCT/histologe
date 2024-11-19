<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240910091939 extends AbstractMigration
{
    public const string DATE_MIGRATION_FAILED_INTERVENTION = '2024-08-29 10:36:19';

    public function getDescription(): string
    {
        return 'Convert intervention dates from local to UTC using timezone from territoire table.';
    }

    public function up(Schema $schema): void
    {
        try {
            foreach ($this->getInterventions() as $intervention) {
                $scheduledAt = new \DateTimeImmutable(
                    $intervention['scheduled_at'],
                    new \DateTimeZone($intervention['timezone'])
                );
                $scheduledUTCAt = $scheduledAt->setTimezone(new \DateTimeZone('UTC'));
                $this->connection->executeStatement(
                    'UPDATE intervention SET scheduled_at = :scheduledAt WHERE id = :interventionId', [
                        'scheduledAt' => $scheduledUTCAt->format('Y-m-d H:i'),
                        'interventionId' => $intervention['id'],
                    ]
                );
            }
        } catch (\Throwable $exception) {
            $this->write($exception->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->beginTransaction();
        try {
            foreach ($this->getInterventions() as $intervention) {
                $scheduledAtUTC = new \DateTimeImmutable(
                    $intervention['scheduled_at'],
                    new \DateTimeZone('UTC')
                );
                $scheduledLocalAt = $scheduledAtUTC->setTimezone(new \DateTimeZone($intervention['timezone']));
                $this->connection->executeStatement(
                    'UPDATE intervention SET scheduled_at = :scheduledAt WHERE id = :interventionId', [
                        'scheduledAt' => $scheduledLocalAt->format('Y-m-d H:i'),
                        'interventionId' => $intervention['id'],
                    ]
                );
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            $this->write($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function getInterventions(): array
    {
        $query = '
            SELECT i.id, i.scheduled_at, t.timezone, i.created_at
            FROM intervention i
            INNER JOIN signalement s ON s.id = i.signalement_id
            INNER JOIN territory t ON t.id = s.territory_id
            WHERE i.scheduled_at IS NOT NULL AND i.created_at < :date_migration_fixed
            ORDER BY i.created_at DESC;';

        return $this->connection->fetchAllAssociative(
            $query,
            ['date_migration_fixed' => self::DATE_MIGRATION_FAILED_INTERVENTION]
        );
    }
}
