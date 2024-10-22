<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241022073450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on service column in job_event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_job_event_service ON job_event (service)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_service ON job_event');
    }
}
