<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124083152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove territory_id from affectation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY `FK_F4DD61D373F74AD4`');
        $this->addSql('DROP INDEX IDX_F4DD61D373F74AD4 ON affectation');
        $this->addSql('ALTER TABLE affectation DROP territory_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD territory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT `FK_F4DD61D373F74AD4` FOREIGN KEY (territory_id) REFERENCES territory (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F4DD61D373F74AD4 ON affectation (territory_id)');
    }
}
