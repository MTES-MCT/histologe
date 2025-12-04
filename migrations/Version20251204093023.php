<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204093023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique nullable integer column reference_injonction to signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD reference_injonction INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F4B55114354A372E ON signalement (reference_injonction)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_F4B55114354A372E ON signalement');
        $this->addSql('ALTER TABLE signalement DROP reference_injonction');
    }
}
