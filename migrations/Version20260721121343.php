<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721121343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on job_event for signalement_id, partner_id, created_at, id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_job_event_signalement_partner_created ON job_event (signalement_id, partner_id, created_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_signalement_partner_created ON job_event');
    }
}
