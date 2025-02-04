<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250130150909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid column to affectation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD uuid VARCHAR(255) NOT NULL AFTER territory_id');
        $this->addSql('UPDATE affectation SET uuid = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F4DD61D3D17F50A6 ON affectation (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_F4DD61D3D17F50A6 ON affectation');
        $this->addSql('ALTER TABLE affectation DROP uuid');
    }
}
