<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250527143737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete suivi entries with type AUTO and empty description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM suivi WHERE type = 1 AND description = ''
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
