<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplacement de la colonne is_public (boolean) par visibility (json) sur la table suivi.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE suivi ADD visibility JSON NOT NULL COMMENT 'Values from SuiviVisibility enum'");
        $this->addSql("UPDATE suivi SET visibility = '[\"PARTENAIRES_AFFECTES\"]' WHERE is_public = 0");
        $this->addSql("UPDATE suivi SET visibility = '[\"PARTENAIRES_AFFECTES\",\"USAGERS\"]' WHERE is_public = 1");

        $this->addSql('DROP INDEX idx_suivi_is_public_signalement_created_at ON suivi');
        $this->addSql('DROP INDEX idx_suivi_signalement_is_public_created_at_category ON suivi');
        $this->addSql('DROP INDEX idx_suivi_is_public_signalement_created_at_type ON suivi');
        $this->addSql('DROP INDEX idx_suivi_signid_cat_createdat_ispublic_createdby ON suivi');

        $this->addSql('CREATE INDEX idx_suivi_signalement_created_at_type ON suivi (signalement_id, created_at, type)');
        $this->addSql('CREATE INDEX idx_suivi_signid_cat_createdat_createdby ON suivi (signalement_id, category, created_at, created_by_id)');

        $this->addSql('ALTER TABLE suivi DROP COLUMN is_public');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD is_public TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql("UPDATE suivi SET is_public = 1 WHERE JSON_CONTAINS(visibility, '\"USAGERS\"')");

        $this->addSql('DROP INDEX idx_suivi_signalement_created_at_type ON suivi');
        $this->addSql('DROP INDEX idx_suivi_signid_cat_createdat_createdby ON suivi');

        $this->addSql('CREATE INDEX idx_suivi_is_public_signalement_created_at ON suivi (is_public, signalement_id, created_at)');
        $this->addSql('CREATE INDEX idx_suivi_signalement_is_public_created_at_category ON suivi (signalement_id, is_public, created_at, category)');
        $this->addSql('CREATE INDEX idx_suivi_is_public_signalement_created_at_type ON suivi (is_public, signalement_id, created_at, type)');
        $this->addSql('CREATE INDEX idx_suivi_signid_cat_createdat_ispublic_createdby ON suivi (signalement_id, category, created_at, is_public, created_by_id)');

        $this->addSql('ALTER TABLE suivi DROP COLUMN visibility');
    }
}
