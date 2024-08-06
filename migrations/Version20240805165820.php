<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240805165820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update history_entry migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry CHANGE event event VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE history_entry RENAME INDEX idx_8f3f68c5a76ed395 TO IDX_72999517A76ED395');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_entry CHANGE event event VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE history_entry RENAME INDEX idx_72999517a76ed395 TO IDX_8F3F68C5A76ED395');
    }
}
