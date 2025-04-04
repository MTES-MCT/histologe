<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250328162438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_operator column to intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD external_operator VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention DROP external_operator');
    }
}
