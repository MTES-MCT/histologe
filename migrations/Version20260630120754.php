<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630120754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on partner_type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_job_event_partner_type ON job_event (partner_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_partner_type ON job_event');
    }
}
