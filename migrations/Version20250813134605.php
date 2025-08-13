<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250813134605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on suivi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_suivi_signalement_createdAt ON suivi (signalement_id, created_at)');
        $this->addSql('CREATE INDEX IDX_suivi_signalement_category_createdAt ON suivi (signalement_id, category, created_at)');
        $this->addSql('CREATE INDEX IDX_suivi_signalement_isPublic_createdAt ON suivi (signalement_id, is_public, created_at)');
        $this->addSql('CREATE INDEX IDX_suivi_signalement_type_createdAt ON suivi (signalement_id, type, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_suivi_signalement_createdAt ON suivi');
        $this->addSql('DROP INDEX IDX_suivi_signalement_category_createdAt ON suivi');
        $this->addSql('DROP INDEX IDX_suivi_signalement_isPublic_createdAt ON suivi');
        $this->addSql('DROP INDEX IDX_suivi_signalement_type_createdAt ON suivi');
    }
}
