<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\JobEvent;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241009143155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove useless indexes on job_event.service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_job_event_service ON job_event');
        $this->addSql("UPDATE job_event SET status = :status_failed WHERE status = 'failure'", [
            'status_failed' => JobEvent::STATUS_FAILED,
        ]);
    }

    public function down(Schema $schema): void
    {
    }
}
