<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241018093257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add column permissions to User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD permissions JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP permissions');
    }
}
