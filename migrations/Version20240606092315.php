<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240606092315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove archivingScheduledAt value for users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user SET archiving_scheduled_at = NULL WHERE archiving_scheduled_at IS NOT NULL');
    }
}
