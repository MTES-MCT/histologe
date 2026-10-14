<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005134913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add procedure_category column to signalement_procedure table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_procedure ADD procedure_category VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE signalement_procedure SET procedure_category = 'PROCEDURE_RETENUE'");
        $this->addSql('ALTER TABLE signalement_procedure CHANGE procedure_category procedure_category VARCHAR(255) NOT NULL');

        $this->addSql('DROP INDEX uniq_signalement_procedure_type ON signalement_procedure');
        $this->addSql('CREATE UNIQUE INDEX uniq_signalement_procedure_type ON signalement_procedure (signalement_id, procedure_type, procedure_category)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement_procedure DROP procedure_category');

        $this->addSql('DROP INDEX uniq_signalement_procedure_type ON signalement_procedure');
        $this->addSql('CREATE UNIQUE INDEX uniq_signalement_procedure_type ON signalement_procedure (signalement_id, procedure_type)');
    }
}
