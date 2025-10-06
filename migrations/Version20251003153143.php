<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251003153143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add login_bailleur field to signalement entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD login_bailleur VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP login_bailleur');
    }
}
