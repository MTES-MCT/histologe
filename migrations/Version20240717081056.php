<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240717081056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add cgu_version_checked to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD cgu_version_checked VARCHAR(255) DEFAULT NULL');

        $statusActive = 1;
        $this->addSql("
            UPDATE user
            SET cgu_version_checked = '05/06/2024'
            WHERE last_login_at IS NOT NULL
              AND statut = ".$statusActive.'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP cgu_version_checked');
    }
}
