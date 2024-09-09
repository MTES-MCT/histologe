<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240820200418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add news columns to track entities and inverse relation entities tag and signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry ADD changes JSON DEFAULT NULL, ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tag_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (signalement_id, tag_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry DROP changes, DROP source');
        $this->addSql('ALTER TABLE tag_signalement DROP PRIMARY KEY, ADD PRIMARY KEY (tag_id, signalement_id)');
    }
}
