<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20260114082040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recréer les affectations ARS SISH pour les Deux-Sevres';
    }

    public function up(Schema $schema): void
    {
        $adminEmail = 'admin@signal-logement.beta.gouv.fr';
        $references = [
            '2024-2425',
            '2024-2452',
            '2024-2455',
            '2025-2507',
            '2024-2401',
            '2025-2525',
            '2025-2506',
            '2024-2444',
            '2025-2528',
            '2025-7',
        ];

        $territoryId = $this->connection->fetchOne('SELECT id FROM territory WHERE zip = 79');
        $userId = $this->connection->fetchOne('SELECT id FROM user WHERE email = ?', [$adminEmail]);
        $partnerId = $this->connection->fetchOne(
            'SELECT id FROM partner WHERE territory_id = ? AND type = "ARS" AND is_esabora_active = 1 AND is_archive = 0',
            [$territoryId]
        );

        foreach ($references as $reference) {
            $signalementId = $this->connection->fetchOne(
                'SELECT id FROM signalement WHERE reference = ? AND territory_id = ?',
                [$reference, $territoryId]
            );

            if ($signalementId) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM affectation WHERE signalement_id = ? AND partner_id = ?',
                    [$signalementId, $partnerId]
                );

                if (!$exists) {
                    $this->addSql('INSERT INTO affectation (
                        uuid, 
                        signalement_id, 
                        partner_id, 
                        statut, 
                        is_synchronized, 
                        answered_at,
                        created_at, 
                        affected_by_id,
                        answered_by_id,
                        territory_id
                    ) VALUES (
                        :uuid,
                        :signalement_id, 
                        :partner_id, 
                        :statut, 
                        :is_synchronized, 
                        NOW(),
                        NOW(), 
                        :affected_by_id, 
                        :answered_by_id,
                        :territory_id
                    )', [
                        'uuid' => Uuid::v4()->toString(),
                        'signalement_id' => $signalementId,
                        'partner_id' => $partnerId,
                        'statut' => 'EN_COURS',
                        'is_synchronized' => 1,
                        'affected_by_id' => $userId,
                        'answered_by_id' => $userId,
                        'territory_id' => $territoryId,
                    ]);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
