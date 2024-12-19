<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241213111647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add info desordres on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD debut_desordres VARCHAR(15) DEFAULT NULL, ADD has_seen_desordres TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP debut_desordres, DROP has_seen_desordres');
    }
}
