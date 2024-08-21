<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240820200418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add news columns to track entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry ADD changes JSON DEFAULT NULL, ADD source VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry DROP changes, DROP source');
    }
}
