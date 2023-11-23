<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231121140808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add is_variants_generated column to file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD is_variants_generated TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP is_variants_generated');
    }
}
