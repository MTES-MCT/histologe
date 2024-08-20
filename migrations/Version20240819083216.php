<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240819083216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UUID to file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE file ADD uuid VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE file SET uuid = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C9F3610D17F50A6 ON file (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8C9F3610D17F50A6 ON file');
        $this->addSql('ALTER TABLE file DROP uuid');
    }
}
