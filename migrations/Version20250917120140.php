<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250917120140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updatedAt field to file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP updated_at');
    }
}
