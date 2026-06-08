<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527134631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add a new column communeMergedInto to commune table to mark deprecated communes after merge';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune ADD commune_merged_into_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commune ADD CONSTRAINT FK_COMMUNE_MERGED_INTO FOREIGN KEY (commune_merged_into_id) REFERENCES commune (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commune DROP FOREIGN KEY FK_COMMUNE_MERGED_INTO');
        $this->addSql('ALTER TABLE commune DROP commune_merged_into_id');
    }
}
