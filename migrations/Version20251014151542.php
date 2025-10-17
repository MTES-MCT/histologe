<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251014151542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des index composites pour accélérer les requêtes JobEvent / Partner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_job_event_created_at_partner_id ON job_event (created_at, partner_id)');
        $this->addSql('CREATE INDEX idx_partner_territory_id ON partner (territory_id)');
        $this->addSql('CREATE INDEX idx_job_event_status_created_at ON job_event (status, created_at)');
        $this->addSql('CREATE INDEX idx_job_event_service_action_created_at ON job_event (service, action, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_created_at_partner_id ON job_event');
        $this->addSql('DROP INDEX idx_partner_territory_id ON partner');
        $this->addSql('DROP INDEX idx_job_event_status_created_at ON job_event');
        $this->addSql('DROP INDEX idx_job_event_service_action_created_at ON job_event');
    }
}
