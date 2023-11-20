<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231114163537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add field size to file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD size BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP size');
    }
}
