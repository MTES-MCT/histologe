<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\User;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240625095546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Desarchive all users archived by command notify-and-archive-inactive-accounts';
    }

    public function up(Schema $schema): void
    {
        $suffixArchived = User::SUFFIXE_ARCHIVED;
        $statusActive = User::STATUS_ACTIVE;
        $statusInactive = User::STATUS_INACTIVE;
        $statusArchive = User::STATUS_ARCHIVE;

        $this->addSql("
            UPDATE user
            SET statut = $statusInactive
            WHERE last_login_at IS NULL
              AND statut = $statusArchive
              AND email NOT LIKE '%$suffixArchived%'
        ");

        $this->addSql("
            UPDATE user
            SET statut = $statusActive
            WHERE last_login_at IS NOT NULL
              AND statut = $statusArchive
              AND email NOT LIKE '%$suffixArchived%'
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
