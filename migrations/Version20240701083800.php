<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240701083800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete esabora-sish useless suivis ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM suivi
            WHERE description LIKE 'Signalement <b> (Dossier %'
            AND created_at > '2024-06-29 00:00:00'
            AND type = 1
            AND is_public = 0
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
