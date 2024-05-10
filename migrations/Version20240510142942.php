<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240510142942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removing old executed migrations from production';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM doctrine_migration_versions WHERE version NOT LIKE "%20240503102522" AND version NOT LIKE "%20240510142942"');
    }

    public function down(Schema $schema): void
    {
    }
}
