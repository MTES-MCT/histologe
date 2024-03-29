<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230105211343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove error type column prorio_averti_at, proprio_averti_at already exists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP prorio_averti_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD prorio_averti_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
