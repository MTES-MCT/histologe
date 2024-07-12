<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240712154158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change competence column type in partner table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner CHANGE competence competence LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE partner CHANGE competence competence TINYTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
