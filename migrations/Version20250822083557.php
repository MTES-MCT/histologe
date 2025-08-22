<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250822083557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on statut and territory on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_statut_territory ON signalement (statut, territory_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_statut_territory ON signalement');
    }
}
