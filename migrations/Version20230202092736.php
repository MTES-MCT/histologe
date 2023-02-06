<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230202092736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create two indexes idx_signalement_statut and idx_signalement_created_at to improve performance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_signalement_statut ON signalement (statut)');
        $this->addSql('CREATE INDEX idx_signalement_created_at ON signalement (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_signalement_statut ON signalement');
        $this->addSql('DROP INDEX idx_signalement_created_at ON signalement');
    }
}
