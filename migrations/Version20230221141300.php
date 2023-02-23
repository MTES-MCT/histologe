<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230221141300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop is_situation_handicap column from signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP is_situation_handicap');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD is_situation_handicap TINYINT(1) DEFAULT NULL');
    }
}
