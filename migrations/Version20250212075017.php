<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250212075017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid column to intervention table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention ADD uuid VARCHAR(255) NOT NULL AFTER partner_id');
        $this->addSql('UPDATE intervention SET uuid = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D11814ABD17F50A6 ON intervention (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D11814ABD17F50A6 ON intervention');
        $this->addSql('ALTER TABLE intervention DROP uuid');
    }
}
