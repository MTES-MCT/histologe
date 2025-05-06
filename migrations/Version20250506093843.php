<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250506093843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create index on context column in suivi table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_suivi_context ON suivi (context)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP INDEX idx_suivi_context ON suivi
        SQL);
    }
}
