<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623121218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée un suivi INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR pour chaque INJONCTION_BAILLEUR_REMINDER_FOR_USAGER existant (backfill historique)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO suivi (created_at, description, is_visible_for_usager, is_visible_for_bailleur, type, signalement_id, is_sanitized, category, waiting_notification)
            SELECT
                su.created_at,
                'Relance envoyée au bailleur pour demander un suivi sur les travaux.',
                0,
                0,
                1,
                su.signalement_id,
                1,
                'INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR',
                0
            FROM suivi su
            WHERE su.category = 'INJONCTION_BAILLEUR_REMINDER_FOR_USAGER'
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM suivi WHERE category = 'INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR'");
    }
}
