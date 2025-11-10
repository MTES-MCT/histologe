<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251110175046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les abonnements manquants pour les agents ayant accepté leur affectation.';
    }

    public function up(Schema $schema): void
    {

        $this->addSql(<<<'SQL'
                INSERT INTO user_signalement_subscription (user_id, signalement_id, created_by_id, created_at, is_legacy)
                SELECT
                    t.user_id,
                    t.signalement_id,
                    t.user_id AS created_by_id,
                    t.created_at AS created_at,
                    1 AS is_legacy
                FROM (
                    SELECT
                        u.id               AS user_id,
                        a.signalement_id   AS signalement_id,
                        -- il y\'a quelques affectations (partenaires différents) mènent au même couple (user, signalement)
                        MIN(a.answered_at) AS created_at
                    FROM affectation a
                    JOIN user u ON u.id = a.answered_by_id
                    WHERE a.statut = 'EN_COURS'
                      AND a.answered_at <= '2025-10-28 12:00:00'
                      AND u.has_done_subscriptions_choice = 1
                      AND NOT JSON_CONTAINS(u.roles, '"ROLE_USAGER"')
                    GROUP BY u.id, a.signalement_id
                ) AS t
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM user_signalement_subscription uss
                    WHERE uss.user_id = t.user_id
                      AND uss.signalement_id = t.signalement_id
                );
                SQL);
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
