<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240903092746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_original_deleted column to file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD is_original_deleted TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP is_original_deleted');
    }
}
