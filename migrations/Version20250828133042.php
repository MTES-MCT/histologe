<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250828133042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add indexes for signalement and suivi tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_statut_id ON signalement (statut, id)');
        $this->addSql('CREATE INDEX idx_suivi_signalement_category_created_at ON suivi (signalement_id, category, created_at)');
        $this->addSql('CREATE INDEX idx_suivi_signalement_is_public_created_at_category ON suivi (signalement_id, is_public, created_at, category)');
        $this->addSql('CREATE INDEX idx_suivi_is_public_signalement_created_at_type ON suivi (is_public, signalement_id, created_at, type)');
        $this->addSql('CREATE INDEX idx_suivi_signid_cat_createdat_ispublic_createdby ON suivi (signalement_id, category, created_at, is_public, created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_statut_id ON signalement');
        $this->addSql('DROP INDEX idx_suivi_signalement_category_created_at ON suivi');
        $this->addSql('DROP INDEX idx_suivi_signalement_is_public_created_at_category ON suivi');
        $this->addSql('DROP INDEX idx_suivi_is_public_signalement_created_at_type ON suivi');
        $this->addSql('DROP INDEX idx_suivi_signid_cat_createdat_ispublic_createdby ON suivi');
    }
}
