<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250627145538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove file_type column from file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE file DROP file_type
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE file ADD file_type VARCHAR(32) NOT NULL COMMENT 'Value possible photo or document'
        SQL);
    }
}
