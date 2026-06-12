<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610135313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update date_entree to date_immutable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement CHANGE date_entree date_entree DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CHANGE date_entree date_entree DATE DEFAULT NULL');
    }
}
