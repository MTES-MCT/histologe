<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251006193044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set partner_id to NULL for Suivi entries with category AFFECTATION_IS_ACCEPTED';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE suivi SET partner_id = NULL WHERE category = 'AFFECTATION_IS_ACCEPTED'");
    }

    public function down(Schema $schema): void
    {
    }
}
