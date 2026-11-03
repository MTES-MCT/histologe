<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102164600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make scheduled_at column NOT NULL in intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention CHANGE scheduled_at scheduled_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE failed_email CHANGE subject subject LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention CHANGE scheduled_at scheduled_at DATETIME NULL');
        $this->addSql('ALTER TABLE failed_email CHANGE subject subject LONGTEXT NULL');
    }
}
