<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230413143515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete old job_event and update job_event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM job_event WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)');
        $this->addSql('ALTER TABLE job_event  ADD partner_type VARCHAR(255) DEFAULT NULL, ADD code_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_event CHANGE title action VARCHAR(255), CHANGE type service VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_event  DROP partner_type, DROP code_status');
        $this->addSql('ALTER TABLE job_event CHANGE action title VARCHAR(255), CHANGE service type VARCHAR(255)');
    }
}
