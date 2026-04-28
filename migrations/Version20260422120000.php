<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renommage de is_public en is_visible_for_usager et ajout de is_visible_for_bailleur sur suivi, et renommage de last_suivi_is_public en last_suivi_is_visible_for_usager sur signalement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement RENAME COLUMN last_suivi_is_public TO last_suivi_is_visible_for_usager');
        $this->addSql('ALTER TABLE suivi RENAME COLUMN is_public TO is_visible_for_usager');
        $this->addSql('ALTER TABLE suivi ADD is_visible_for_bailleur TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_is_public_signalement_created_at TO idx_suivi_is_visible_for_usager_signalement_created_at');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_is_public_signalement_created_at_type TO idx_suivi_is_visible_for_usager_signalement_created_at_type');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_signalement_is_public_created_at_category TO idx_suivi_signalement_is_visible_for_usager_created_at_category');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_signid_cat_createdat_ispublic_createdby TO idx_suivi_signid_cat_createdat_is_visible_for_usager_createdby');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_is_visible_for_usager_signalement_created_at TO idx_suivi_is_public_signalement_created_at');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_is_visible_for_usager_signalement_created_at_type TO idx_suivi_is_public_signalement_created_at_type');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_signalement_is_visible_for_usager_created_at_category TO idx_suivi_signalement_is_public_created_at_category');
        $this->addSql('ALTER TABLE suivi RENAME INDEX idx_suivi_signid_cat_createdat_is_visible_for_usager_createdby TO idx_suivi_signid_cat_createdat_ispublic_createdby');

        $this->addSql('ALTER TABLE suivi DROP COLUMN is_visible_for_bailleur');
        $this->addSql('ALTER TABLE suivi RENAME COLUMN is_visible_for_usager TO is_public');
        $this->addSql('ALTER TABLE signalement RENAME COLUMN last_suivi_is_visible_for_usager TO last_suivi_is_public');
    }
}
