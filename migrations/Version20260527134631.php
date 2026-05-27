<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527134631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add a new column is_deprecated to commune table to mark deprecated communes after merge';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune ADD is_deprecated BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune DROP is_deprecated');
    }
}
