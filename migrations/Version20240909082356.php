<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240909082356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add original_data column to suivi table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD original_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP original_data');
    }
}
