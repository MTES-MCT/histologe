<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231114155429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new fields (document type and desordre slug)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD document_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD desordre_slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file DROP document_type');
        $this->addSql('ALTER TABLE file DROP desordre_slug');
    }
}
