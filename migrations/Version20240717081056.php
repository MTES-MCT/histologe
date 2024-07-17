<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\User;
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

        $this->addSql("
            UPDATE user
            SET cgu_version_checked = '3'
            WHERE last_login_at IS NOT NULL
              AND statut = " . User::STATUS_ACTIVE . "
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP cgu_version_checked');
    }
}
