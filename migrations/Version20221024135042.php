<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221024135042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add is_imported to Signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD is_imported BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE signalement SET is_imported = 1 WHERE YEAR(created_at) < 2022 AND territory_id != 64 AND territory_id != 59');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP is_imported');
    }
}
