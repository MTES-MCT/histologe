<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250418112346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new column to tag suspicious pdf';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD is_suspicious TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP is_suspicious');
    }
}
