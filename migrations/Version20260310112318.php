<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310112318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add mail_occupant_temp on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD mail_occupant_temp VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP mail_occupant_temp');
    }
}
