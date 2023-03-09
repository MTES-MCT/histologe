<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230309103809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_suivi_at on signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD last_suivi_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP last_suivi_at');
    }
}
