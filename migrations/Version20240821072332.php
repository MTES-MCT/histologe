<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240821072332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_filename in User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD avatar_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP avatar_filename');
    }
}
