<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124111346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_cgu_tiers_accepted column to Signalement entity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD is_cgu_tiers_accepted TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP is_cgu_tiers_accepted');
    }
}
