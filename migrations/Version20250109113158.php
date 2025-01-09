<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250109113158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix value of isAllocataire field for signalements created after 2024-12-20';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->getSignalements() as $signalement) {
            $payload = json_decode($signalement['payload'], true);
            if ($payload && isset($payload['logement_social_allocation']) && 'non' === $payload['logement_social_allocation']) {
                $this->connection->executeStatement(
                    'UPDATE signalement s
                    SET s.is_allocataire = \'O\'
                    WHERE s.id = :signalement_id',
                    [
                        'signalement_id' => $signalement['id'],
                    ]
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getSignalements(): array
    {
        $query =
            'SELECT s.id, s.is_allocataire, sd.id as draft_id, sd.payload
             FROM signalement s
             LEFT JOIN signalement_draft sd ON s.created_from_id = sd.id
             WHERE s.created_at >= :createdAt
             AND (s.is_allocataire IS NULL OR s.is_allocataire = \'\')
             AND (s.created_from_id IS NOT NULL)'
        ;

        return $this->connection->fetchAllAssociative(
            $query,
            ['createdAt' => '2024-12-20']
        );
    }

    public function down(Schema $schema): void
    {
    }
}
