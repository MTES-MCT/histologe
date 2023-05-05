<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230505122047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change conclude_procedure type from string to array';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention CHANGE conclude_procedure conclude_procedure TINYTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE intervention CHANGE conclude_procedure conclude_procedure VARCHAR(255) DEFAULT NULL');
    }
}
