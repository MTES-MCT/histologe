<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240729083340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add field partner_to_exclude to auto_affectation_rule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule ADD partner_to_exclude JSON DEFAULT NULL COMMENT \'Value possible null or an array of partner ids\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auto_affectation_rule DROP partner_to_exclude');
    }
}
