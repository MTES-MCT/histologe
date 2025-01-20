<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120141313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_sanitized column to suivi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD is_sanitized TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP is_sanitized');
    }
}
