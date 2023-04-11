<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230411121503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Define is_imported to false when null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET is_imported = 0 WHERE is_imported IS NULL');       

    }

    public function down(Schema $schema): void
    {

    }
}
