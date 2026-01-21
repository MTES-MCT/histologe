<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121140919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create column for ProfileOccupant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD profile_occupant VARCHAR(255) DEFAULT NULL COMMENT \'Value possible enum ProfileOccupant\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP profile_occupant');
    }
}
