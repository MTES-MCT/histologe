<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250611083312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reset Doctrine Migrations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM doctrine_migration_versions');
    }

    public function down(Schema $schema): void
    {
    }
}
