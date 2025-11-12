<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105112836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new columns to job_event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_event ADD attachments_count INT DEFAULT NULL, ADD attachments_size INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_event DROP attachments_count, DROP attachments_size');
    }
}
