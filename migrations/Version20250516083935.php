<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250516083935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add extension to File entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD extension VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP extension');
    }
}
