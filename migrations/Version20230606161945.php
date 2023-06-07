<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230606161945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add filed in order to select which signalement need to be sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation ADD is_synchronized TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE affectation DROP is_synchronized');
    }
}
