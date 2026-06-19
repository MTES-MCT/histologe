<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616145448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add territory_id column to address table, and add created_by_id, imported_at, identifiant_parcellaire columns to arrete table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD territory_id INT NOT NULL');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8173F74AD4 FOREIGN KEY (territory_id) REFERENCES territory (id)');
        $this->addSql('CREATE INDEX IDX_D4E6F8173F74AD4 ON address (territory_id)');

        $this->addSql('ALTER TABLE arrete ADD created_by_id INT DEFAULT NULL, ADD imported_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD identifiant_parcellaire VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE arrete ADD CONSTRAINT FK_8D9860AB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D9860AB03A8386 ON arrete (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8173F74AD4');
        $this->addSql('DROP INDEX IDX_D4E6F8173F74AD4 ON address');
        $this->addSql('ALTER TABLE address DROP territory_id');

        $this->addSql('ALTER TABLE arrete DROP FOREIGN KEY FK_8D9860AB03A8386');
        $this->addSql('DROP INDEX IDX_8D9860AB03A8386 ON arrete');
        $this->addSql('ALTER TABLE arrete DROP created_by_id, DROP imported_at, DROP identifiant_parcellaire');
    }
}
