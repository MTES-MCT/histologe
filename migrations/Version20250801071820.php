<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801071820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create index on history_entry for entity_id, event, and entity_name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_history_entry_entityid_event_entityname ON history_entry (entity_id, event, entity_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_history_entry_entityid_event_entityname ON history_entry');
    }
}
