<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240916094002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set is_original_deleted to false for all files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `file` SET `is_original_deleted`=0 WHERE `is_original_deleted` = 1');
    }

    public function down(Schema $schema): void
    {
    }
}
