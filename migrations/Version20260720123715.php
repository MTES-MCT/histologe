<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720123715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée un suivi INJONCTION_BAILLEUR_LOGIN_BAILLEUR à la date de première connexion du bailleur pour chaque signalement concerné';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO suivi (
                created_at,
                description,
                is_visible_for_usager,
                is_visible_for_bailleur,
                type,
                signalement_id,
                is_sanitized,
                category,
                waiting_notification
            )
            SELECT
                MIN(h.created_at),
                '',
                0,
                0,
                4,
                h.entity_id,
                1,
                'INJONCTION_BAILLEUR_LOGIN_BAILLEUR',
                0
            FROM history_entry h
            INNER JOIN signalement s ON s.id = h.entity_id
            WHERE h.event = 'LOGIN'
              AND h.source = '/connexion-bailleur'
            GROUP BY h.entity_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM suivi WHERE category = 'INJONCTION_BAILLEUR_LOGIN_BAILLEUR'");
    }
}
