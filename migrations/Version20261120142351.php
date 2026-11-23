<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120142351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set fields NOT NULL in suivi and auto_affectation_rule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE territory_id territory_id INT NOT NULL');
        $this->addSql('ALTER TABLE suivi CHANGE category category VARCHAR(255) NOT NULL COMMENT \'Value possible enum SuiviCategory\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE territory_id territory_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi CHANGE category category VARCHAR(255) DEFAULT NULL COMMENT \'Value possible enum SuiviCategory\'');
    }
}
