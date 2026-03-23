<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323105455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d\'un suivi "SIGNALEMENT_IS_INJONCTION" à la date de création du signalement pour les signalements ayant une référence injonction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO suivi (
                created_at,
                created_by_id,
                description,
                is_public,
                type,
                signalement_id,
                is_sanitized,
                category,
                waiting_notification
            )
            SELECT
                h.created_at,
                (SELECT u.id FROM user u WHERE u.email = 'admin@signal-logement.beta.gouv.fr' LIMIT 1),
                'Dossier déposé dans le cadre de la démarche accélérée',
                0,
                1,
                s.id,
                1,
                'SIGNALEMENT_IS_INJONCTION',
                0
            FROM history_entry h
            INNER JOIN signalement s ON h.entity_id = s.id
            LEFT JOIN suivi su ON s.id = su.signalement_id AND su.category = 'SIGNALEMENT_IS_INJONCTION'
            WHERE h.event = 'CREATE'
              AND h.entity_name = 'App\\Entity\\Signalement'
              AND s.reference_injonction IS NOT NULL
              AND su.id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
