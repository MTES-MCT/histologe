<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240719101956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add scanned_at column to file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD scanned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP scanned_at');
    }
}
