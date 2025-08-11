<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250814055703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de deux index composites sur la table suivi :
    - (category, signalement_id, created_at) pour optimiser la recherche des relances usager par signalement et date
    - (is_public, signalement_id, created_at) pour accélérer la récupération du dernier suivi public partagé à l’usager';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_suivi_category_signalement_created_at ON suivi (category, signalement_id, created_at)');
        $this->addSql('CREATE INDEX idx_suivi_is_public_signalement_created_at ON suivi (is_public, signalement_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_suivi_category_signalement_created_at ON suivi');
        $this->addSql('DROP INDEX idx_suivi_is_public_signalement_created_at ON suivi');
    }
}
