<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250818062234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on suivi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_suivi_signalement_created_at ON suivi (signalement_id, created_at)');
        $this->addSql('CREATE INDEX idx_suivi_category ON suivi (category)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_suivi_signalement_created_at ON suivi');
        $this->addSql('DROP INDEX idx_suivi_category ON suivi');
    }
}
