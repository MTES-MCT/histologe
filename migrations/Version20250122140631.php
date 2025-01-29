<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250122140631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'AutoAffectationRule change values of insee_to_include ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE insee_to_include insee_to_include VARCHAR(255) NOT NULL COMMENT \'Value possible empty or an array of code insee\'');
        $this->addSql("UPDATE auto_affectation_rule SET insee_to_include = '' WHERE insee_to_include IN ('all','partner_list')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule CHANGE insee_to_include insee_to_include VARCHAR(255) NOT NULL COMMENT \'Value possible all, partner_list or an array of code insee\'');
    }
}
