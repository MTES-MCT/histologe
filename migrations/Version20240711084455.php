<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240711084455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate references for signalements in the Oise (territory_id=61) and Seine-Maritime (territory_id=77)';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        $signalementsOise = $this->getSignalements($connection, 61);

        foreach ($signalementsOise as $signalement) {
            $newReference = $this->generateNewReference($connection, $signalement, 61);
            if ($newReference) {
                $this->updateSignalementReference($connection, $signalement['id'], $newReference);
            }
        }

        $signalementsSeineMaritime = $this->getSignalements($connection, 77);

        foreach ($signalementsSeineMaritime as $signalement) {
            $newReference = $this->generateNewReference($connection, $signalement, 77);
            if ($newReference) {
                $this->updateSignalementReference($connection, $signalement['id'], $newReference);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // This migration is not reversible.
    }

    private function getSignalements(Connection $connection, int $territoryId): array
    {
        return $connection->fetchAllAssociative(
            'SELECT uuid, id, reference, is_imported, created_at
            FROM signalement
            WHERE territory_id = :territoryId',
            ['territoryId' => $territoryId]
        );
    }

    private function generateNewReference(Connection $connection, array $signalement, int $territoryId): ?string
    {
        $isImported = (bool) $signalement['is_imported'];
        $createdAt = new \DateTime($signalement['created_at']);
        $year = $createdAt->format('Y');

        if ($isImported) {
            $reference = $signalement['reference'];
            if (61 === $territoryId && preg_match('/^\d{6}[a-z]?-(\d{3})$/', $reference, $matches)) {
                $newReference = sprintf('%s-%s', $year, substr($reference, 2, 4));
            } elseif (77 === $territoryId && preg_match('/^\d{5}\s*-\s*\d{4}$/', $reference, $matches)) {
                $newReference = sprintf('%s-%s', $year, substr($reference, 2, 3));
            } else {
                $newReference = null;
            }
        } else {
            $reference = $signalement['reference'];
            if (!preg_match('/^\d{4}-\d+$/', $reference)) {
                $lastReferenceNumber = $this->getLastReferenceNumberForYear($connection, $year, $territoryId);
                $newReference = sprintf('%s-%s', $year, $lastReferenceNumber + 1);
            } else {
                $newReference = null;
            }
        }

        $suffix = '';
        while ($newReference && !$this->isReferenceUniqueInTerritory($connection, $newReference.$suffix, $territoryId)) {
            $suffix .= '0';
        }
        $newReference .= $suffix;

        return $newReference;
    }

    private function getLastReferenceNumberForYear(Connection $connection, string $year, int $territoryId): int
    {
        $result = $connection->fetchOne(
            'SELECT CAST(SUBSTRING_INDEX(s.reference, \'-\', -1) AS SIGNED) AS reference_index
            FROM signalement s
            WHERE YEAR(s.created_at) = :year
            AND territory_id = :territoryId
            AND s.reference REGEXP :regex
            ORDER BY `reference_index` DESC
            LIMIT 1',
            [
                'territoryId' => $territoryId,
                'year' => $year,
                'regex' => '^[0-9]{4}-[0-9]+$',
            ]
        );

        return $result ? (int) $result : 0;
    }

    private function isReferenceUniqueInTerritory(Connection $connection, string $reference, int $territoryId): bool
    {
        $result = $connection->fetchOne(
            'SELECT COUNT(*)
            FROM signalement
            WHERE territory_id = :territoryId
            AND reference = :reference',
            [
                'territoryId' => $territoryId,
                'reference' => $reference,
            ]
        );

        return 0 === (int) $result;
    }

    private function updateSignalementReference(Connection $connection, int $signalementId, string $newReference): void
    {
        $connection->executeStatement(
            'UPDATE signalement SET reference = :newReference WHERE id = :id',
            ['newReference' => $newReference, 'id' => $signalementId]
        );
    }
}
