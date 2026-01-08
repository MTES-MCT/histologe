<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260108101547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase length of insee_to_include field in auto_affectation_rule table to 500 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE insee_to_include insee_to_include VARCHAR(500) NOT NULL COMMENT \'Value possible empty or an array of code insee\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE insee_to_include insee_to_include VARCHAR(255) NOT NULL COMMENT \'Value possible empty or an array of code insee\'');
    }
}
