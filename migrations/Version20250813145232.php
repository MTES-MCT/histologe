<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250813145232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on is_standalone column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_is_standalone ON file (is_standalone)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_is_standalone ON file');
    }
}
