<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251218111544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to improve query performance on signalement_draft, user, and suivi tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_draft_uuid ON signalement_draft (uuid)');
        $this->addSql('CREATE INDEX idx_user_statut ON user (statut)');
        $this->addSql('CREATE INDEX idx_suivi_waiting_notification ON suivi (waiting_notification)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_draft_uuid ON signalement_draft');
        $this->addSql('DROP INDEX idx_user_statut ON user');
        $this->addSql('DROP INDEX idx_suivi_waiting_notification ON suivi');
    }
}
