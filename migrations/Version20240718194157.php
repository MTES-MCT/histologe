<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240718194157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on suivi, signalement and job_event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_suivi_created_at ON suivi (created_at)');
        $this->addSql('CREATE INDEX idx_suivi_type ON suivi (type)');

        $this->addSql('CREATE INDEX idx_signalement_is_imported ON signalement (is_imported)');
        $this->addSql('CREATE INDEX idx_signalement_uuid ON signalement (uuid)');

        $this->addSql('CREATE INDEX idx_job_event_created_at ON job_event (created_at)');
        $this->addSql('CREATE INDEX idx_job_event_service ON job_event (service)');
        $this->addSql('CREATE INDEX idx_job_event_partner_id ON job_event (partner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_suivi_created_at ON suivi');
        $this->addSql('DROP INDEX idx_suivi_type ON suivi');

        $this->addSql('DROP INDEX idx_signalement_uuid ON signalement');
        $this->addSql('DROP INDEX idx_signalement_is_imported ON signalement');

        $this->addSql('DROP INDEX idx_job_event_created_at ON job_event');
        $this->addSql('DROP INDEX idx_job_event_service ON job_event');
        $this->addSql('DROP INDEX idx_job_event_partner_id ON job_event');
    }
}
