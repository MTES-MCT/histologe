<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240626180734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set is_waiting_suivi flag to true on File created since more than 1 hour';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE file SET is_waiting_suivi = 0 WHERE created_at < DATE_SUB(NOW(), INTERVAL '1' HOUR)");
    }
}
