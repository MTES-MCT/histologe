<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017150657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set created_at of Signalement from HistoryEntry when created from DRAFT';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE signalement s
            INNER JOIN history_entry h ON s.id = h.entity_id AND h.entity_name = 'App\\Entity\\Signalement'
            SET s.created_at = h.created_at
            WHERE s.statut NOT IN ('DRAFT', 'DRAFT_ARCHIVED')
            AND s.created_by_id IS NOT NULL
            AND JSON_EXTRACT(h.changes, '$.statut.old') = 'DRAFT'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
