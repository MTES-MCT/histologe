<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250226160205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update User_id of HistoryEntry event create of affectation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
        UPDATE history_entry h
        INNER JOIN affectation a ON h.entity_id = a.id
        SET h.user_id = a.affected_by_id
        WHERE h.entity_name LIKE '%Affectation'
          AND h.event LIKE 'CREATE'
          AND h.user_id IS NOT NULL
          AND h.user_id <> a.affected_by_id
    ");
    }

    public function down(Schema $schema): void
    {
    }
}
