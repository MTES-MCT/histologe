<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241206084748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_suivi_signalement_type_created_at ON suivi (signalement_id, type, created_at)');
        $this->addSql('CREATE INDEX idx_signalement_code_suivi ON signalement (code_suivi)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_suivi_signalement_type_created_at ON suivi');
        $this->addSql('DROP INDEX idx_signalement_code_suivi ON signalement');
    }
}
