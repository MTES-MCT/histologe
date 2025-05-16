<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250514141901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category column to suivi table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi ADD category VARCHAR(255) DEFAULT NULL COMMENT 'Value possible enum SuiviCategory'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE suivi DROP category
        SQL);
    }
}
