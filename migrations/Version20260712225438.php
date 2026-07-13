<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712225438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_operational_error column to job_event table to improve performance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_event ADD is_operational_error TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX idx_job_event_is_operational_error ON job_event (is_operational_error)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_is_operational_error ON job_event');
        $this->addSql('ALTER TABLE job_event DROP is_operational_error');
    }
}
