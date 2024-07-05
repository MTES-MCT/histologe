<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240704155656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add checksum column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi ADD checksum VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suivi DROP checksum');
    }
}
