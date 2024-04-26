<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240426100957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_temp column to file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD is_temp TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP is_temp');
    }
}
