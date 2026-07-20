<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720082350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change column name type_arrete to arrete_type in arrete table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete CHANGE type_arrete arrete_type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE arrete CHANGE arrete_type type_arrete VARCHAR(255) NOT NULL');
    }
}
